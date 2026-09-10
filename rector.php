<?php

declare(strict_types=1);

use PhpParser\Node\Expr\Cast\Bool_;
use PhpParser\Node\Expr\Cast\Double;
use PhpParser\Node\Expr\Cast\Int_;
use PhpParser\Node\Expr\Cast\String_;
use Rector\Config\RectorConfig;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\ValueObject\PhpVersion;
use Rector\Php83\Rector\FuncCall\CombineHostPortLdapUriRector;
use Rector\Php83\Rector\FuncCall\RemoveGetClassGetParentClassNoArgsRector;
use Rector\Php84\Rector\Param\ExplicitNullableParamTypeRector;
use Rector\Php85\Rector\ClassMethod\NullDebugInfoReturnRector;
use Rector\Php85\Rector\FuncCall\ArrayKeyExistsNullToEmptyStringRector;
use Rector\Php85\Rector\FuncCall\ChrArgModuloRector;
use Rector\Php85\Rector\FuncCall\OrdSingleByteRector;
use Rector\Php85\Rector\FuncCall\RemoveFinfoBufferContextArgRector;
use Rector\Php85\Rector\ShellExec\ShellExecFunctionCallOverBackticksRector;
use Rector\Php85\Rector\Switch_\ColonAfterSwitchCaseRector;
use Rector\Removing\Rector\FuncCall\RemoveFuncCallArgRector;
use Rector\Removing\Rector\FuncCall\RemoveFuncCallRector;
use Rector\Removing\ValueObject\RemoveFuncCallArg;
use Rector\Renaming\Rector\Cast\RenameCastRector;
use Rector\Renaming\Rector\ConstFetch\RenameConstantRector;
use Rector\Renaming\Rector\FuncCall\RenameFunctionRector;
use Rector\Renaming\Rector\MethodCall\RenameMethodRector;
use Rector\Renaming\ValueObject\MethodCallRename;
use Rector\Renaming\ValueObject\RenameCast;

// Rules intentionally excluded because they require PHP > 8.2 syntax/functions:
//
// PHP 8.3+: AddOverrideAttributeToOverriddenMethodsRector, AddTypeToConstRector,
//           ReadOnlyAnonymousClassRector, DynamicClassConstFetchRector, JsonValidateRector
//
// PHP 8.4+: RoundingModeEnumRector, NewMethodCallWithoutParenthesesRector,
//           DeprecatedAnnotationToDeprecatedAttributeRector, ForeachToArray*Rector
//
// PHP 8.5+: ArrayFirstLastRector, ConstAndTraitDeprecatedAttributeRector,
//           AddOverrideAttributeToOverriddenPropertiesRector,
//           RenameClassConstFetchRector (PDO → Pdo\* subclasses require PHP 8.4+)
//
// Excluded because PHP 8.5 does not raise E_DEPRECATED for __sleep()/__wakeup():
//           SleepToSerializeRector, WakeupToUnserializeRector

return RectorConfig::configure()
    ->withPhpVersion(PhpVersion::PHP_85)
    ->withImportNames(importShortClasses: false)
    ->withPaths([
        __DIR__ . '/lib',
        __DIR__ . '/test',
        __DIR__ . '/examples',
    ])
    ->withSets([
        // PHPUnit metadata deprecation: doc-comment @dataProvider/@covers/@group/@depends/@test
        // etc. → PHP attributes. Has no effect if the repo has no PHPUnit suite.
        //
        // PHPUNIT_100 carries the PHPUnit 10 rules that make data provider methods static and
        // public; PHPUNIT_110 covers the PHPUnit 11 rules. Both sets exist up to Rector 2.6.1 and
        // are removed in 2.6.2, so composer.json pins rector/rector to 2.6.1 exactly.
        PHPUnitSetList::PHPUNIT_100,
        PHPUnitSetList::PHPUNIT_110,
        PHPUnitSetList::ANNOTATIONS_TO_ATTRIBUTES,
    ])
    ->withRules([
        // PHP 8.3: deprecated calling get_class()/get_parent_class() without arguments inside a class
        RemoveGetClassGetParentClassNoArgsRector::class,
        // PHP 8.3: deprecated separate host/port params for ldap_connect()
        CombineHostPortLdapUriRector::class,
        // PHP 8.4: deprecated implicit nullable params, e.g. function foo(Type $x = null)
        ExplicitNullableParamTypeRector::class,
        // PHP 8.5: deprecated finfo_*() context argument
        RemoveFinfoBufferContextArgRector::class,
        // PHP 8.5: deprecated __debugInfo() returning null — must return array
        NullDebugInfoReturnRector::class,
        // PHP 8.5: deprecated semicolons after switch case labels (should be colons)
        ColonAfterSwitchCaseRector::class,
        // PHP 8.5: deprecated array_key_exists(null, $array)
        ArrayKeyExistsNullToEmptyStringRector::class,
        // PHP 8.5: deprecated chr() with out-of-range values
        ChrArgModuloRector::class,
        // PHP 8.5: deprecated ord() with multi-byte strings
        OrdSingleByteRector::class,
        // PHP 8.5: deprecated backtick operator — use shell_exec() instead
        ShellExecFunctionCallOverBackticksRector::class,
    ])
    ->withConfiguredRule(RemoveFuncCallArgRector::class, [
        // PHP 8.5: deprecated key_length parameter of openssl_pkey_derive()
        new RemoveFuncCallArg('openssl_pkey_derive', 2),
        // PHP 8.5: deprecated exclude_disabled parameter of get_defined_functions()
        new RemoveFuncCallArg('get_defined_functions', 0),
    ])
    ->withConfiguredRule(RenameMethodRector::class, [
        // PHP 8.5: deprecated SplObjectStorage::contains/attach/detach in favour of ArrayAccess methods
        new MethodCallRename('SplObjectStorage', 'contains', 'offsetExists'),
        new MethodCallRename('SplObjectStorage', 'attach', 'offsetSet'),
        new MethodCallRename('SplObjectStorage', 'detach', 'offsetUnset'),
    ])
    ->withConfiguredRule(RenameFunctionRector::class, [
        // PHP 8.5: deprecated socket_set_timeout() alias
        'socket_set_timeout' => 'stream_set_timeout',
        // PHP 8.5: deprecated mysqli_execute() alias
        'mysqli_execute' => 'mysqli_stmt_execute',
    ])
    ->withConfiguredRule(RenameCastRector::class, [
        // PHP 8.5: deprecated non-standard cast names — (integer), (boolean), (double), (binary)
        new RenameCast(Int_::class, Int_::KIND_INTEGER, Int_::KIND_INT),
        new RenameCast(Bool_::class, Bool_::KIND_BOOLEAN, Bool_::KIND_BOOL),
        new RenameCast(Double::class, Double::KIND_DOUBLE, Double::KIND_FLOAT),
        new RenameCast(String_::class, String_::KIND_BINARY, String_::KIND_STRING),
    ])
    ->withConfiguredRule(RenameConstantRector::class, [
        // PHP 8.5: FILTER_DEFAULT is a deprecated alias for FILTER_UNSAFE_RAW
        'FILTER_DEFAULT' => 'FILTER_UNSAFE_RAW',
    ])
    ->withConfiguredRule(RemoveFuncCallRector::class, [
        // PHP 8.5: these functions do nothing after resource-to-object conversions
        'curl_close',
        'curl_share_close',
        'finfo_close',
        'imagedestroy',
        'xml_parser_free',
    ]);
