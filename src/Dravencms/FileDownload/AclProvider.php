<?php declare(strict_types=1);

namespace Dravencms\FileDownload;

use Dravencms\User\Acl\{AclPermission, AclProviderInterface};

final class AclProvider implements AclProviderInterface
{
    public function getPermissions(): iterable
    {
        yield new AclPermission('fileDownload', 'edit', 'fileDownload.acl.permissions.edit.label', resourceLabel: 'fileDownload.acl.resource');
        yield new AclPermission('fileDownload', 'delete', 'fileDownload.acl.permissions.delete.label', resourceLabel: 'fileDownload.acl.resource');
    }
}
