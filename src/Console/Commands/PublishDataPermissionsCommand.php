<?php

namespace BrightLiu\LowCode\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PublishDataPermissionsCommand extends Command
{
    /**
     * 命令名称和签名
     */
    protected $signature = 'low-code:publish-data-permissions 
                            {--f : 强制覆盖已存在的数据}';

    /**
     * 命令描述
     */
    protected $description = '发布LowCode包的数据权限配置';

    /**
     * 默认数据配置
     */
    protected $defaultData = [
        [
            'disease_code' => '',
            'permission_key' => 'manage_org_code',
            'code' => 'org',
            'title' => '纳管机构',
            'symbol' => 'in',
        ],
        [
            'disease_code' => '',
            'permission_key' => 'multiple_field',
            'code' => 'region',
            'title' => '地区编码',
            'symbol' => 'multiple_field',
        ]
    ];

    /**
     * 执行命令
     */
    public function handle()
    {
        $tableName = 'data_permissions';

        // 检查表是否存在
        if (!Schema::hasTable($tableName)) {
            $this->error("❌ 数据表 {$tableName} 不存在，请先创建表结构");
            return 1;
        }

        $this->info('🚀 开始发布数据权限配置...');

        $successCount = 0;
        $skipCount = 0;

        foreach ($this->defaultData as $data) {
            $result = $this->publishData($data);

            if ($result === 'created') {
                $successCount++;
                $this->info("✅ 创建: {$data['title']} ({$data['code']})");
            } elseif ($result === 'updated') {
                $successCount++;
                $this->info("🔄 更新: {$data['title']} ({$data['code']})");
            } else {
                $skipCount++;
                $this->warn("⏭️  跳过: {$data['title']} ({$data['code']}) - 已存在");
            }
        }

        $this->info("\n🎉 数据发布完成！");
        $this->info("✅ 成功: {$successCount} 条");
        $this->info("⏭️  跳过: {$skipCount} 条");

        return 0;
    }

    /**
     * 发布单条数据
     */
    protected function publishData(array $data): string
    {
        $now = now();
        $fullData = array_merge($data, [
            'created_at' => $now,
            'updated_at' => $now,
            'deleted_at' => null,
        ]);

        // 检查是否已存在
        $exists = DB::table('data_permissions')
            ->where('code', $data['code'])
            ->exists();

        if (!$exists) {
            // 创建新记录
            DB::table('data_permissions')->insert($fullData);
            return 'created';
        }

        // 如果已存在，根据 f 选项决定是否更新
        if ($this->option('f')) {
            DB::table('data_permissions')
                ->where('code', $data['code'])
                ->update($fullData);
            return 'updated';
        }

        return 'skipped';
    }

    /**
     * 显示命令帮助信息
     */
    public function handleHelp()
    {
        $this->line('使用方法:');
        $this->line('  php artisan low-code:publish-data-permissions        # 发布数据，已存在则跳过');
        $this->line('  php artisan low-code:publish-data-permissions --f # 强制覆盖已存在的数据');
        $this->line('');
        $this->line('发布的数据包括:');
        $this->line('  - org    : 纳管机构权限 (in 操作符)');
        $this->line('  - region : 地区编码权限 (多字段模式)');
    }
}