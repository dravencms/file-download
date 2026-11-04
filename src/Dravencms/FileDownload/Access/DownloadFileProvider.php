<?php declare(strict_types=1);
namespace Dravencms\FileDownload\Access;
use Salamek\Files\Access\{IFileAccessProvider, FileAccess};
use Dravencms\Model\FileDownload\Repository\DownloadFileTranslationRepository;
use Dravencms\File\Access\AdminFileAccess;

final class DownloadFileProvider implements IFileAccessProvider
{
    public function __construct(private DownloadFileTranslationRepository $repository, private AdminFileAccess $user) {}
    public static function getId(): string { return 'download.attachment'; }
    public function resolve(string $reference): ?FileAccess
    {
        $id = filter_var($reference, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id === false || !($entity = $this->repository->getOneById($id))) { return null; }
        $public = true;
        if (!$public && !$this->user->isAllowed('fileDownload', 'edit')) { return null; }
        $attachment = $entity->getStructureFile();
        return $attachment ? new FileAccess($attachment->getFile(), $public, $attachment->getBasename()) : null;
    }
}
