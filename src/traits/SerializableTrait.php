<?php

namespace App\traits;

trait SerializableTrait
{

    /**
     * @Exclude
     */
    private static $annotationCache = [];



    public function serialize(array $userRoles = [], bool $short = false): array
    {
        $reflection = new \ReflectionClass($this);
        $properties = $reflection->getProperties();

        $defaultBehavior = $this->getClassDefaultBehavior($reflection);

        $serialized = [];

        foreach ($properties as $property) {
            $exposeAnnotation = $this->parseExposeAnnotation($property);
            $excludeAnnotation = $this->parseExcludeAnnotation($property);
            $shortAnnotation = $this->parseShortAnnotation($property);
            $serializeAsAnnotation = $this->parseSerializeAsAnnotation($property);

            if ($excludeAnnotation) {
                continue; // Skip this property
            }

            if ($short && !$shortAnnotation) {
                continue; // Skip non-short properties when short serialization is requested
            }

            if ($exposeAnnotation || $defaultBehavior === 'expose') {
                $groups = $exposeAnnotation['groups'] ?? [];
                if (empty($groups) || array_intersect($groups, $userRoles)) {
                    $property->setAccessible(true);
                    $value = $property->getValue($this);

                    if ($serializeAsAnnotation) {
                        $value = $this->serializeAs($value, $serializeAsAnnotation);
                    }

                    $serialized[$property->getName()] = $value;
                }
            }
        }

        return $serialized;
    }
    private function serializeAs($value, array $serializeAsAnnotation): mixed
    {
        $type = $serializeAsAnnotation['type'];
        $of = $serializeAsAnnotation['of'];

        if ($type === 'object') {
            return $this->serializeAsObject($value, $of);
        } elseif ($type === 'array') {
            return $this->serializeAsArray($value, $of);
        }

        return $value;
    }


    private function serializeAsObject($value, string $className): mixed
    {
        if (!is_object($value)) {
            $value = $this->instantiate($className, $value);
        }

        if (method_exists($value, 'serialize')) {
            return $value->serialize();
        }

        return $value;
    }

    private function serializeAsArray($value, string $className): array
    {
        if (!is_array($value)) {
            return [];
        }

        return array_map(function ($item) use ($className) {
            if (!is_object($item)) {
                $item = $this->instantiate($className, $item);
            }

            if (method_exists($item, 'serialize')) {
                return $item->serialize();
            }

            return $item;
        }, $value);
    }


    private function instantiate(string $className, $data)
    {
        if (!class_exists($className)) {
            throw new \RuntimeException("Class {$className} does not exist");
        }

        $reflection = new \ReflectionClass($className);

        if ($reflection->getConstructor() && $reflection->getConstructor()->getNumberOfParameters() > 0) {
            return $reflection->newInstance($data);
        } else {
            $instance = $reflection->newInstance();
            if (is_array($data)) {
                foreach ($data as $key => $value) {
                    if (property_exists($instance, $key)) {
                        $instance->$key = $value;
                    }
                }
            }
            return $instance;
        }
    }
    private function getClassDefaultBehavior(\ReflectionClass $reflection): string
    {
        $className = $reflection->getName();
        if (!isset(self::$annotationCache[$className]['defaultBehavior'])) {
            $docComment = $reflection->getDocComment();
            if (strpos($docComment, '@Expose') !== false) {
                self::$annotationCache[$className]['defaultBehavior'] = 'expose';
            } elseif (strpos($docComment, '@Exclude') !== false) {
                self::$annotationCache[$className]['defaultBehavior'] = 'exclude';
            } else {
                self::$annotationCache[$className]['defaultBehavior'] = 'expose'; // Default to expose if no annotation is present
            }
        }
        return self::$annotationCache[$className]['defaultBehavior'];
    }

    private function parseExposeAnnotation(\ReflectionProperty $property): ?array
    {
        $className = $property->getDeclaringClass()->getName();
        $propertyName = $property->getName();

        if (!isset(self::$annotationCache[$className]['properties'][$propertyName]['expose'])) {
            $docComment = $property->getDocComment();
            if (preg_match('/@Expose(\(groups=\{"(.+?)"\}\))?/', $docComment, $matches)) {
                self::$annotationCache[$className]['properties'][$propertyName]['expose'] = [
                    'groups' => isset($matches[2]) ? explode('","', $matches[2]) : []
                ];
            } else {
                self::$annotationCache[$className]['properties'][$propertyName]['expose'] = null;
            }
        }
        return self::$annotationCache[$className]['properties'][$propertyName]['expose'];
    }

    private function parseExcludeAnnotation(\ReflectionProperty $property): bool
    {
        $className = $property->getDeclaringClass()->getName();
        $propertyName = $property->getName();

        if (!isset(self::$annotationCache[$className]['properties'][$propertyName]['exclude'])) {
            $docComment = $property->getDocComment();
            self::$annotationCache[$className]['properties'][$propertyName]['exclude'] = strpos($docComment, '@Exclude') !== false;
        }
        return self::$annotationCache[$className]['properties'][$propertyName]['exclude'];
    }

    private function parseShortAnnotation(\ReflectionProperty $property): bool
    {
        $className = $property->getDeclaringClass()->getName();
        $propertyName = $property->getName();

        if (!isset(self::$annotationCache[$className]['properties'][$propertyName]['short'])) {
            $docComment = $property->getDocComment();
            self::$annotationCache[$className]['properties'][$propertyName]['short'] = strpos($docComment, '@Short') !== false;
        }
        return self::$annotationCache[$className]['properties'][$propertyName]['short'];
    }

    private function parseSerializeAsAnnotation(\ReflectionProperty $property): ?array
    {
        $className = $property->getDeclaringClass()->getName();
        $propertyName = $property->getName();

        if (!isset(self::$annotationCache[$className]['properties'][$propertyName]['serializeAs'])) {
            $docComment = $property->getDocComment();
            if (preg_match('/@SerializeAs\(type="(object|array)"(?:,\s*of="(.+?)")?\)/', $docComment, $matches)) {
                self::$annotationCache[$className]['properties'][$propertyName]['serializeAs'] = [
                    'type' => $matches[1],
                    'of' => $matches[2] ?? null
                ];
            } else {
                self::$annotationCache[$className]['properties'][$propertyName]['serializeAs'] = null;
            }
        }
        return self::$annotationCache[$className]['properties'][$propertyName]['serializeAs'];
    }
}