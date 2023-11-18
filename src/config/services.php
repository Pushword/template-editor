<?php

declare(strict_types=1);

use Pushword\Core\PushwordCoreBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->defaults()
        ->autowire()
        ->autoconfigure()
        ->bind('$disableCreation', '%pw.pushword_template_editor.disable_creation%')
        ->bind('$canBeEditedList', '%pw.pushword_template_editor.can_be_edited_list%');

    $services->load('Pushword\TemplateEditor\\', __DIR__.'/../*')
        ->exclude([
            __DIR__.'/../'.PushwordCoreBundle::SERVICE_AUTOLOAD_EXCLUDE_PATH,
        ]);
};
