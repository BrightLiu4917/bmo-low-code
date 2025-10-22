<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$rules = [
    // 应用所有Symfony编码标准规则
    '@Symfony' => true,

    // 类定义规则
    'class_attributes_separation' => [
        'elements' => [
            'const' => 'one',
            'method' => 'one',
            'property' => 'one',
        ],
    ],

    // 类定义样式
    'class_definition' => [
        'multi_line_extends_each_single_line' => true,
        'single_item_single_line' => true,
        'single_line' => true,
        'space_before_parenthesis' => true,
    ],

    // 字符串连接时的空格规则
    'concat_space' => [
        'spacing' => 'one',
    ],

    // 显示间接变量引用
    'explicit_indirect_variable' => true,

    // 字符串中使用变量时添加大括号
    'explicit_string_variable' => true,

    // 换行标签后添加换行
    'linebreak_after_opening_tag' => true,

    // + 自增/自减运算符的样式
    'increment_style' => ['style' => 'post'],

    // 强制使用逻辑运算符而不是控制结构
    'logical_operators' => true,

    // 使用mb_函数代替str函数
    'mb_str_functions' => true,

    // 方法链式调用缩进
    'method_chaining_indentation' => true,

    // 多行空白行的处理
    'no_extra_blank_lines' => [
        'tokens' => [
            'extra',
            'throw',
            'use',
            'use_trait',
            'square_brace_block',
            'curly_brace_block',
            'parenthesis_brace_block',
            'return',
            'continue',
            'break',
            'switch',
            'case',
            'default',
        ],
    ],

    // 禁止使用PHP4风格构造函数
    'no_php4_constructor' => true,

    // 多行空白处理
    'multiline_whitespace_before_semicolons' => [
        'strategy' => 'no_multi_line',
    ],

    // 禁止使用无法访问的默认参数值
    'no_unreachable_default_argument_value' => true,

    // 自动添加缺少的参数注释
    'phpdoc_add_missing_param_annotation' => ['only_untyped' => false],

    // PHPDoc对齐方式
    'phpdoc_align' => ['align' => 'left'],

    // PHPDoc摘要规则
    'phpdoc_summary' => false,

    // 每个trait插入语句是否应该在一行
    'single_trait_insert_per_statement' => false,

    // PHP-CS-Fixer 3的规则重命名
    'general_phpdoc_tag_rename' => true,
    'phpdoc_inline_tag_normalizer' => true,
    'phpdoc_tag_type' => true,
    'phpdoc_to_comment' => false,
    'trailing_comma_in_multiline' => ['elements' => ['arrays']],

    // 排序导入语句
    'ordered_imports' => [
        'sort_algorithm' => 'alpha',
    ],
];

$finder = Finder::create()->ignoreDotFiles(true);

return (new Config())
    ->setFinder($finder)
    ->setRules($rules)
    ->setRiskyAllowed(true)
    ->setUsingCache(true);
