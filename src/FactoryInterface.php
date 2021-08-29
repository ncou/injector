<?php

declare(strict_types=1);

namespace Chiron\Injector;

interface FactoryInterface
{
    /*
     * @param string $className
     * @param array  $arguments
     *
     * @return object
     */
    // TODO : ajouter le typehint pour le retour de la fonction avec "make(): object"
    public function build(string $className, array $arguments = []);
}
