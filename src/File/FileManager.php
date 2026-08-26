<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\File;

use Lexio\AdminBundle\Contract\File\FileEntityInterface;
use Lexio\AdminBundle\Contract\File\ImageEntityInterface;
use Lexio\AdminBundle\Utils\AdminUtils;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\AsciiSlugger;

class FileManager
{
    public function __construct(
        private readonly Filesystem $fileSystem,
        private readonly string     $projectDir,
        private readonly string     $siteBaseUrl,
    ) {
    }

    /**
     * Creates an UploadedFile from a path on disk.
     *
     * @throws \InvalidArgumentException When the file does not exist.
     */
    public function createUploadedFile(string $path): UploadedFile
    {
        $info = new \SplFileInfo($path);

        if (!$info->isFile()) {
            throw new \InvalidArgumentException('File not found: ' . $path);
        }

        return new UploadedFile($info->getRealPath(), $info->getBasename());
    }

    /**
     * Creates an UploadedFile from raw binary content.
     */
    public function createFileFromBinary(string $content, string $originalFileName): UploadedFile
    {
        $tempPath = (string) tempnam(sys_get_temp_dir(), 'upload_');
        file_put_contents($tempPath, $content);

        return new UploadedFile($tempPath, $originalFileName);
    }

    public function uploadFile(
        UploadedFile       $uploadedFile,
        FileEntityInterface $fileEntity,
    ): FileEntityInterface|ImageEntityInterface {
        $slugger          = new AsciiSlugger();
        $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
        $newFilename      = $slugger->slug($originalFilename)
            . '-' . AdminUtils::randomString(11)
            . '.' . $uploadedFile->guessExtension();

        $fileEntity
            ->setName($newFilename)
            ->setOriginalName($originalFilename)
            ->setMime($uploadedFile->getMimeType())
            ->setSize($uploadedFile->getSize());

        if ($fileEntity instanceof ImageEntityInterface) {
            $fileEntity->setAlt($originalFilename);
        }

        $stream = fopen($uploadedFile->getPathname(), 'r');

        if ($stream === false) {
            throw new \RuntimeException('Failed to open uploaded file: ' . $uploadedFile->getPathname());
        }

        $this->fileSystem->dumpFile(
            $this->projectDir . '/' . $fileEntity->accessType()->directory() . $fileEntity->getDirectory() . '/' . $newFilename,
            $stream,
        );

        return $fileEntity;
    }

    public function delete(FileEntityInterface $file): void
    {
        $this->fileSystem->remove($this->getSystemPath($file));
    }

    public function getSystemPath(FileEntityInterface $fileEntity): string
    {
        return $this->projectDir . '/' . $fileEntity->accessType()->directory() . $fileEntity->getFilePath();
    }

    public function getAbsoluteUrl(FileEntityInterface $fileEntity): string
    {
        return rtrim($this->siteBaseUrl, '/') . '/' . ltrim($fileEntity->getFilePath(), '/');
    }
}

