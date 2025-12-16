<?php

declare(strict_types=1);

namespace BrightLiu\LowCode\Requests\Resident\ResidentMaintenance;

use Gupo\BetterLaravel\Validation\BaseRequest;

final class FileImportRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'file' => ['bail', 'required', 'file'],
        ];
    }

    public function attributes(): array
    {
        return [
            'file' => '文件',
        ];
    }

    public function messages()
    {
        return [
            'file.required' => '文件必传',
        ];
    }
}
