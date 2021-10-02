<?php

declare(strict_types=1);

namespace Chiron\Injector\Traits;

use Chiron\Injector\Exception\CannotResolveException;
use Chiron\Injector\ResolvingState;
use Psr\Container\ContainerExceptionInterface;
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
    protected function resolveDependencies(?ReflectionFunctionAbstract $reflection = null, array $arguments = []): array
    {
        // VARIADIC : https://github.com/yiisoft/definitions/blob/ebf3091722cf9c4e71672571010e25fe81566084/src/Infrastructure/DefinitionExtractor.php#L85
        //https://github.com/yiisoft/injector/blob/3bd38d4ebc70f39050e4ae056ac10c40c4975cb1/src/Injector.php#L176
        //https://github.com/nette/di/blob/3dd8ca66d4d64fed9815099928139539d9d8b3e3/src/DI/Resolver.php#L487
        // https://github.com/illuminate/container/blob/c2b6cc5807177579231df5dcb49d31e3a183f71e/Container.php#L985

        // ReflectionIntersectionType : https://github.com/nette/di/blob/3dd8ca66d4d64fed9815099928139539d9d8b3e3/src/DI/Resolver.php#L531
        // ReflectionUnionType : https://github.com/nette/di/blob/3dd8ca66d4d64fed9815099928139539d9d8b3e3/src/DI/Resolver.php#L590

        $state = new ResolvingState($reflection, $arguments);

        foreach ($reflection->getParameters() as $parameter) {
            // Try to resolve parameters
            $resolved = $this->resolveParameter($parameter, $arguments, $state);

            if ($resolved === true) {
                continue;
            }

            //throw new MissingRequiredArgumentException($reflection, $parameter->getName());
            throw new CannotResolveException($parameter);
        }

        // TODO : lever une exception si le parametre n'est pas optionnel et qu'on n'a pas réussi à le résoudre !!!! https://github.com/illuminate/container/blob/c2b6cc5807177579231df5dcb49d31e3a183f71e/BoundMethod.php#L177

        // TODO : ajouter ici une méthode qui vérifie les type de paramétres checkTypes => https://github.com/symfony/dependency-injection/blob/5.3/Compiler/CheckTypeDeclarationsPass.php#L128

        //return $arguments; // TODO : renommer les variables $arguments et $value en $results et $result ????

        return $state->getResolvedValues();
    }

    private function resolveParameter(ReflectionParameter $parameter, array $parameters = [], ResolvingState $state): bool
    {
        $isVariadic = $parameter->isVariadic();
        $hasType = $parameter->hasType();
        // TODO : méthode à virer !!!!
        $state->disablePushTrailingArguments($isVariadic && $hasType); // TODO : voir pourquoi on a besoin du hasType = true !!!

        $name = $parameter->getName();
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

            // We can't resolve a variadic parameter with a classname using the container 'foobar(\StdClass ...$myClasses)'
            if ($isVariadic) {
                return false; // TODO : not pas forcément il peut il y avoir une valeur par défaut je pense !!!! 'foobar(?\StdClass ...$myClasses)' ou 'foobar(?\StdClass ...$myClasses = null)'
            }

            // https://github.com/yiisoft/yii2/blob/68a1c32400cbba297ce45dc1b3ab6bfc597903a2/framework/di/Container.php#L692
            // https://github.com/nette/di/blob/3dd8ca66d4d64fed9815099928139539d9d8b3e3/src/DI/Resolver.php#L545
            try {
                //Requesting for contextual dependency
                $value = $this->container->get($class);
                // Faire le catch plutot sur le Container\Psr\NotFoundExceptionInterface::class
            } catch (ContainerExceptionInterface $e) {
                // TODO : Utipliser plutot le default value : https://github.com/illuminate/container/blob/c2b6cc5807177579231df5dcb49d31e3a183f71e/Container.php#L994
                if ($parameter->isOptional()) {
                    //This is optional dependency, skip.
                    $value = null;
                }

                throw $e;
                // TODO : il faudrait plutot renvoyer une CannotResolveException ou une DependencyException du genre : https://github.com/PHP-DI/PHP-DI/blob/78278800b18e7c5582fd4d4e629715f5eebbfcc0/src/Definition/Resolver/ObjectCreator.php#L147
            }
        } elseif ($parameter->isDefaultValueAvailable()) {
            $value = $parameter->getDefaultValue();
        } elseif (! $parameter->isOptional()) {
            //$funcName = $reflection->getName();
            //throw new InvalidConfigException("Missing required parameter \"$name\" when calling \"$funcName\".");

            //throw new CannotResolveException($parameter);

            //$message = "Unable to resolve dependency [{$parameter}] in class {$parameter->getDeclaringClass()->getName()}";
            return false;
        }

        $state->addResolvedValue($value);

        return true;
    }

    /**
     * Get the class name of the given parameter's type, if possible.
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
