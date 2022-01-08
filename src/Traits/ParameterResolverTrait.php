<?php

declare(strict_types=1);

namespace Chiron\Injector\Traits;

use Chiron\Injector\Exception\MissingRequiredParameterException;
use Chiron\Injector\ResolvingState;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionFunctionAbstract;
use ReflectionNamedType;
use ReflectionParameter;

// INTERSECTION :
//https://github.com/nette/di/blob/ad12717ec0493ff2a4f6bd7c06e98f4c1a05fb4c/src/DI/Resolver.php#L532
// UNION : Remove the "xxx|null" part
//https://github.com/nette/utils/blob/master/src/Utils/ReflectionType.php#L48

//https://github.com/nette/di/blob/f3608c4d8684c880c2af0cf7b4d2b7143bc459b0/src/DI/Resolver.php#L531
//https://github.com/thephpleague/container/blob/82a57588c630663d2600f046753b23ab6dcda9b5/src/Argument/ArgumentResolverTrait.php#L66

// TODO : regarder l'utilisation du isBuiltinType()
//https://github.com/nette/di/blob/f3608c4d8684c880c2af0cf7b4d2b7143bc459b0/src/DI/Resolver.php#L491

//https://github.com/yiisoft/yii2/blob/68a1c32400cbba297ce45dc1b3ab6bfc597903a2/framework/di/Container.php#L500
//https://github.com/yiisoft/yii2/blob/68a1c32400cbba297ce45dc1b3ab6bfc597903a2/framework/di/Container.php#L662

// TODO : utiliser un "cache" pour la reflection des paramétres de la méthode histoire de gagner du temps :
//https://github.com/qunity/dependency-injection/blob/42c5eaac9d184d9db2e717ddf4f1c30b65a7ba91/src/DependencyInjection/Helper/Reflection.php#L56
//https://github.com/yiisoft/yii2/blob/68a1c32400cbba297ce45dc1b3ab6bfc597903a2/framework/di/Container.php#L502

// TODO : gérer le reflectionuniontype;;class du PHP8
//https://github.com/qunity/dependency-injection/blob/42c5eaac9d184d9db2e717ddf4f1c30b65a7ba91/src/DependencyInjection/Processor/CollectArguments.php#L132
//https://github.com/spiral/framework/blob/1a8851523ad1eb62bcbb50be7eff47646c711692/src/Core/src/Container.php#L110
//https://github.com/romanzipp/DTO/blob/5adff07cb90a578f5ee3785d1242932bb1b221dd/src/Types/UnionType.php#L21
//https://github.com/IMPHP/reflect/blob/ffad353065144068005b0b018345d6226ba3558f/src/reflect/ReflectUnionType.php
//https://github.com/suvera/winter-boot/blob/06d06230054faa155c5d585e2ac996b08621cf4b/src/reflection/ReflectionUtil.php#L100

//https://github.com/yiisoft/injector/blob/master/src/Injector.php#L121

//https://github.com/illuminate/container/blob/master/BoundMethod.php#L158

//https://github.com/auraphp/Aura.Di/blob/4.x/src/Resolver/Resolver.php#L268

//https://github.com/symfony/symfony/blob/e60a876201b5b306d0c81a24d9a3db997192079c/src/Symfony/Component/DependencyInjection/Compiler/AutowirePass.php#L188

// TODO : renommer en DependenciesResolverTrait ou DependencyResolverTrait ???? ou ArgumentResolverTrait
trait ParameterResolverTrait
{
    // TODO : getTypeHint
    //https://github.com/symfony/dependency-injection/blob/006f585b01f51188a8b30be06df64d1a489d5dec/LazyProxy/ProxyHelper.php


    //https://github.com/researchgate/injektor/blob/master/src/rg/injektor/DependencyInjectionContainer.php#L768
    // TODO : il faudrait pas changer le type ReflectionFunctionAbstract en ReflectionMethod (car on ne va pas vraiment dait de reflection sur une fonction globale) ????
    // TODO : renommer en resolveDependencies() ???
    //https://github.com/thephpleague/container/blob/82a57588c630663d2600f046753b23ab6dcda9b5/src/Argument/ArgumentResolverTrait.php#L66
    // TODO : exemple pour gérer les paramétres qui ne sont pas avec un tableau associatif : https://github.com/illuminate/container/blob/c2b6cc5807177579231df5dcb49d31e3a183f71e/BoundMethod.php#L127
    /**
     * @throws ContainerExceptionInterface Error while retrieving the entry from container.
     * @throws MissingRequiredParameterException
     * @throws InjectorException Error if the $arguments array contains Non-object argument not named explicitly.
     * @throws InvalidParameterTypeException Error if the resolved dependency has not the right type hint.
     */
    protected function resolveDependencies(ReflectionFunctionAbstract $reflection, array $arguments = []): array
    {
        // VARIADIC : https://github.com/yiisoft/definitions/blob/ebf3091722cf9c4e71672571010e25fe81566084/src/Infrastructure/DefinitionExtractor.php#L85
        //https://github.com/yiisoft/injector/blob/3bd38d4ebc70f39050e4ae056ac10c40c4975cb1/src/Injector.php#L176
        //https://github.com/nette/di/blob/3dd8ca66d4d64fed9815099928139539d9d8b3e3/src/DI/Resolver.php#L487
        // https://github.com/illuminate/container/blob/c2b6cc5807177579231df5dcb49d31e3a183f71e/Container.php#L985

        // ReflectionIntersectionType : https://github.com/nette/di/blob/3dd8ca66d4d64fed9815099928139539d9d8b3e3/src/DI/Resolver.php#L531
        // ReflectionUnionType : https://github.com/nette/di/blob/3dd8ca66d4d64fed9815099928139539d9d8b3e3/src/DI/Resolver.php#L590

        $state = new ResolvingState($reflection, $arguments);

        foreach ($reflection->getParameters() as $parameter) {
            // Try to resolve parameters using arguments array.
            $resolved = $this->resolveParameter($parameter, $state);

            if ($resolved === true) {
                continue;
            }

            // Unable to resolve dependency.
            throw new MissingRequiredParameterException($parameter);
            // TODO : renommer la classe en DependencyException par exemple : https://github.com/PHP-DI/PHP-DI/blob/78278800b18e7c5582fd4d4e629715f5eebbfcc0/src/Definition/Resolver/ObjectCreator.php#L147
        }

        // TODO : lever une exception si le parametre n'est pas optionnel et qu'on n'a pas réussi à le résoudre !!!! https://github.com/illuminate/container/blob/c2b6cc5807177579231df5dcb49d31e3a183f71e/BoundMethod.php#L177

        return $state->getResolvedValues();
    }

    /**
     * @throws ContainerExceptionInterface Error while retrieving the entry from container.
     */
    //https://github.com/symfony/symfony/blob/24680199a8c3b7b3ffc2f0e50f96b77b62975b90/src/Symfony/Component/Serializer/Normalizer/AbstractNormalizer.php#L362
    // TODO : virer le 2eme paramétre $parameters de cette méthode car il n'est pas utilisé !!!!
    private function resolveParameter(ReflectionParameter $parameter, ResolvingState $state): bool
    {
        $name = $parameter->getName();
        $isVariadic = $parameter->isVariadic();

        // Try to resolve parameter by name
        if ($state->resolveParameterByName($name, $isVariadic)) {
            return true;
        }

        // Class name is null if there is no typehint or in case of scalar or in case of Union/Intersection typehint (php8.0/8.1).
        $class = $this->getParameterClassName($parameter);
        if ($class !== null) {
            if ($state->resolveParameterByClass($class, $isVariadic)) {
                return true;
            }

            // TODO : vérifier le cas ou on passe un non-array pour un paramétre variadic normalement cela devrait lever une erreur :   https://github.com/nette/di/blob/16f7d617d8ec5a08b0c4700f4cfc488fde4ed457/src/DI/Resolver.php#L506
            // We can't resolve a variadic parameter with a classname using the container 'foobar(\StdClass ...$myClasses)'
            if ($isVariadic) {
                return false; // TODO : non pas forcément il peut il y avoir une valeur par défaut je pense !!!! 'foobar(?\StdClass ...$myClasses)' ou 'foobar(?\StdClass ...$myClasses = null)'
            }

            // TODO : faire un container->has() au lieu du try/catch ????
            // https://github.com/yiisoft/yii2/blob/68a1c32400cbba297ce45dc1b3ab6bfc597903a2/framework/di/Container.php#L692
            // https://github.com/nette/di/blob/3dd8ca66d4d64fed9815099928139539d9d8b3e3/src/DI/Resolver.php#L545
            try {
                //https://github.com/yiisoft/yii-event/blob/master/src/CallableFactory.php#L71
                //Requesting for contextual dependency
                $value = $this->container->get($class); // TODO : eventuellement faire un : if $container->has(XXXX) { $container->get(XXX) } plutot que de faire un try/catch !!!
            } catch (NotFoundExceptionInterface $e) {
                // TODO : Utiliser plutot le default value : https://github.com/illuminate/container/blob/c2b6cc5807177579231df5dcb49d31e3a183f71e/Container.php#L994
                // TODO : optimiser le code car on fait deux fois la vérification sur isOptional dans cette méthode !!!
                if (! $parameter->isOptional()) {
                    return false;
                }

                // This is an optional class dependency, skip value.
                $value = null;
            }
        } elseif ($parameter->isDefaultValueAvailable()) {
            $value = $parameter->getDefaultValue();
        } elseif (! $parameter->isOptional()) {
            return false;
        }

        $state->addResolvedValue($value);

        return true;
    }

    /**
     * Get the class name of the given parameter's type, if possible.
     * Union/Intersection type hint that cannot be inferred unambiguously so we don't return a classname.
     *
     * From Reflector::getParameterClassName() in Illuminate\Support.
     *
     * @param  \ReflectionParameter  $parameter
     *
     * @return string|null
     */
    // https://github.com/illuminate/container/blob/c2b6cc5807177579231df5dcb49d31e3a183f71e/Util.php#L52
    // TODO : attention il faudrait pouvoir gérer le cas du ReflectionUnionType qui a un "nom de classe + null" par exemple foobar(StdClass|null $myClass) il faudrait retourner juste le nom de la classe et s'assurer que la valeur de la reflection "isOptional" est bien à true dans ce type de cas !!!!
    private function getParameterClassName(ReflectionParameter $parameter): ?string
    {
        $type = $parameter->getType();

        if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
            return null;
        }

        $name = $type->getName();

        if (! is_null($class = $parameter->getDeclaringClass())) {
            if ($name === 'self') {
                return $class->getName();
            }

            if ($name === 'parent' && $parent = $class->getParentClass()) {
                return $parent->getName();
            }
        }

        return $name;
    }
}
