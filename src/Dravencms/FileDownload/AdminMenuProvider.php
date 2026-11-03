<?php declare(strict_types=1);

namespace Dravencms\FileDownload;

use Dravencms\Admin\Menu\{AdminMenuItem, AdminMenuProviderInterface};

final class AdminMenuProvider implements AdminMenuProviderInterface
{
    public function getItems(): iterable
    {
        yield new AdminMenuItem('fileDownload.overview', 'fileDownload.menu.overview', destination: ':Admin:File:Download:default', parent: 'content', icon: 'fa-download', order: 30, resource: 'fileDownload', privilege: 'edit');
    }
}
