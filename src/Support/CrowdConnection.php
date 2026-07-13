<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Support;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use BrightLiu\LowCode\Tools\BetterArr;
use BrightLiu\LowCode\Services\LowCode\DatabaseSourceService;
use BrightLiu\LowCode\Core\DbConnectionManager;
use BrightLiu\LowCode\Context\DiseaseContext;

class CrowdConnection
{
    public static function select(string $query, array $bindings = [], bool $useReadPdo = true, ?string $diseaseCode = null, ?string $sceneCode = null): array
    {
        return BetterArr::toArray(
            self::connection($diseaseCode, $sceneCode)->select($query, $bindings, $useReadPdo)
        );
    }

    public static function query(?string $diseaseCode = null, ?string $sceneCode = null): Builder
    {
        $connection = self::connection($diseaseCode, $sceneCode);

        return $connection->table($connection->getConfig('table'));
    }

    public static function table(string $table, ?string $diseaseCode = null, ?string $sceneCode = null): Builder
    {
        return self::connection($diseaseCode, $sceneCode)->table($table);
    }

    public static function connection(?string $diseaseCode = null, ?string $sceneCode = null): Connection
    {
        $diseaseCode ??= DiseaseContext::instance()->getDiseaseCode();
        $sceneCode ??= DiseaseContext::instance()->getSceneCode();

        return DbConnectionManager::getInstance()
            ->getConnection(DatabaseSourceService::instance()->getDataByDiseaseCode($diseaseCode, $sceneCode));
    }
}
