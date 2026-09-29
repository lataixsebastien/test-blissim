<?php

declare(strict_types=1);

use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Yaml\Yaml;

return static function (ContainerConfigurator $container, ContainerBuilder $builder): void {
    $path = dirname(__DIR__).'/openapi.yaml';
    $builder->addResource(new FileResource($path));
    $container->extension('nelmio_api_doc', ['documentation' => Yaml::parseFile($path)]);
};
