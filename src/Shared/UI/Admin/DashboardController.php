<?php

declare(strict_types=1);

namespace App\Shared\UI\Admin;

use App\CallToAction\ManageCtaMessages\UI\Admin\CtaMessageCrudController;
use App\Contact\ManageContactLinks\UI\Admin\ContactLinkCrudController;
use App\Contact\ManageContactMessages\UI\Admin\ContactMessageCrudController;
use App\Content\ManageBlocks\UI\Admin\BlockCollectionCrudController;
use App\Content\ManagePages\UI\Admin\PageCrudController;
use App\Identity\ManageAdminUsers\UI\Admin\AdminUserCrudController;
use App\Identity\Shared\Application\AdminUserRoleEnum;
use App\Portfolio\ManageStatistics\UI\Admin\StatisticGroupCrudController;
use App\Portfolio\ManageTasks\UI\Admin\TaskCrudController;
use App\Portfolio\ManageTechnologies\UI\Admin\TechnologyGroupCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\UserMenu;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;

#[AdminDashboard(routePath: '/admin/{_locale}', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
        return $this->render('shared/admin/dashboard.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->renderContentMaximized()
            ->setTitle('
                      <img src="/favicon.ico" alt="logo" style="width: 28px; height: 28px;">
                      <span class="text-small mx-1">Workfolio</span>
                      ')
            ->setFaviconPath('icons/favicon.svg')
            ->setLocales(LocaleLabels::labelsWithFlagValues())
            ->setTranslationDomain('admin')
        ;
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('menu.dashboard', 'fa fa-home');

        yield MenuItem::linkToUrl('menu.go_to_front', 'fa fa-globe', $this->generateUrl('app_home_root'));

        yield MenuItem::section('section.cms');

        yield MenuItem::linkTo(PageCrudController::class, 'menu.page', 'fas fa-file');

        yield MenuItem::linkTo(BlockCollectionCrudController::class, 'menu.block_collection', 'fas fa-object-group');

        yield MenuItem::linkTo(ContactLinkCrudController::class, 'menu.contact_link', 'fas fa-link');

        yield MenuItem::linkTo(CtaMessageCrudController::class, 'menu.cta_message', 'fas fa-bullhorn');

        yield MenuItem::linkTo(TechnologyGroupCrudController::class, 'menu.technology', 'fas fa-microchip');

        yield MenuItem::linkTo(StatisticGroupCrudController::class, 'menu.statistic', 'fas fa-chart-pie');

        yield MenuItem::linkTo(TaskCrudController::class, 'menu.task', 'fas fa-list-check');

        yield MenuItem::section('section.crm');

        yield MenuItem::linkTo(ContactMessageCrudController::class, 'menu.contact_message', 'fas fa-envelope');

        if ($this->isGranted(AdminUserRoleEnum::ADMIN->value)) {
            yield MenuItem::section('section.admin');

            yield MenuItem::linkTo(AdminUserCrudController::class, 'menu.admin_user', 'fas fa-users');
        }
    }

    public function configureUserMenu(UserInterface $user): UserMenu
    {
        return parent::configureUserMenu($user)
            ->addMenuItems([
                MenuItem::linkToUrl('menu.go_to_front', 'fa fa-globe', $this->generateUrl('app_home_root')),
            ])
        ;
    }
}
