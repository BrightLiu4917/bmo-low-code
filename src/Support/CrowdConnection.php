<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Support;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use BrightLiu\LowCode\Tools\BetterArr;
use BrightLiu\LowCode\Services\LowCode\LowCodeDatabaseSourceService;
use BrightLiu\LowCode\Core\DbConnectionManager;
use BrightLiu\LowCode\Context\DiseaseContext;

class CrowdConnection
{
    public static function select(string $query, array $bindings = [], bool $useReadPdo = true, ?string $diseaseCode = null): array
    {
        return BetterArr::toArray(
            self::connection($diseaseCode)->select($query, $bindings, $useReadPdo)
        );
    }

    public static function query(?string $diseaseCode = null): Builder
    {
        $connection = self::connection($diseaseCode);

        return $connection->table($connection->getConfig('table'));
    }

    public static function table(string $table, ?string $diseaseCode = null): Builder
    {
        return self::connection($diseaseCode)->table($table);
    }

    public static function connection(?string $diseaseCode = null): Connection
    {
        $diseaseCode ??= DiseaseContext::instance()->getDiseaseCode();

        return DbConnectionManager::getInstance()
            ->getConnection(LowCodeDatabaseSourceService::instance()->getDataByDiseaseCode($diseaseCode));
    }
}
