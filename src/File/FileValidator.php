<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\File;

use Lexio\AdminBundle\Enum\FileTypes;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints\File as FileConstraint;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Validates uploaded files against configurable size and MIME-type constraints.
 */
final class FileValidator
{
    /** @var array<string, list<\Symfony\Component\Validator\Constraint>> */
    private array $rules = [];

    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly string $maxUploadSize = '10M',
        private readonly string $imageMaxSize = '5M',
    ) {
        $this->rules = $this->buildRules();
    }

    public function validate(FileTypes $fileType, UploadedFile $uploadedFile): ConstraintViolationListInterface
    {
        return $this->validator->validate($uploadedFile, $this->rules[$fileType->value]);
    }

    /** @return array<string, list<\Symfony\Component\Validator\Constraint>> */
    private function buildRules(): array
    {
        $maxUploadSize   = $this->maxUploadSize;
        $imageMaxSize    = $this->imageMaxSize;

        $fileConstraint = new FileConstraint(
            maxSize: $maxUploadSize,
            mimeTypes: [
                'application/pdf',
                'application/msword',
                'application/rtf',
                'text/plain',
                'text/csv',
                'text/rtf',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-excel',
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'application/zip',
                'application/x-zip-compressed',
                'application/octet-stream',
                'image/jpeg',
                'image/png',
                'image/gif',
                'image/webp',
                'image/svg+xml',
                'audio/mpeg',
                'audio/wav',
                'video/mp4',
                'video/mpeg',
            ],
            mimeTypesMessage: 'unsupported_file_type',
            extensions: [
                // documents
                'pdf', 'odf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'rtf', 'txt', 'csv',
                // OpenOffice
                'odt', 'odp', 'odg',
                // archives
                'zip', 'rar', '7z',
                // images
                'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'avif',
                // audio
                'mp3', 'wav',
                // video
                'mp4', 'mpeg', 'mpg', 'mov', 'avi', 'wmv', 'webm',
            ],
        );

        return [
            FileTypes::IMAGE->value    => [new Image(maxSize: $imageMaxSize), $fileConstraint],
            FileTypes::DOCUMENT->value => [$fileConstraint],
        ];
    }
}



