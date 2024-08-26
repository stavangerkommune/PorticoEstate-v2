<?php

namespace App\modules\bookingfrontend\traits;

trait SerializableTrait
{
    private static $annotationCache = [];

    public function serialize(array $userRoles = []): array
    {
        $reflection = new \ReflectionClass($this);
        $properties = $reflection->getProperties();

        $defaultBehavior = $this->getClassDefaultBehavior($reflection);

        $serialized = [];

        foreach ($properties as $property) {
            $exposeAnnotation = $this->parseExposeAnnotation($property);
            $excludeAnnotation = $this->parseExcludeAnnotation($property);

            if ($excludeAnnotation) {
                continue; // Skip this property
            }

            if ($exposeAnnotation) {
                $groups = $exposeAnnotation['groups'] ?? [];
                if (empty($groups) || array_intersect($groups, $userRoles)) {
                    $property->setAccessible(true);
                    $serialized[$property->getName()] = $property->getValue($this);
                }
            } elseif ($defaultBehavior === 'expose') {
                // If there's no @Expose or @Exclude annotation, and the default is to expose
                $property->setAccessible(true);
                $serialized[$property->getName()] = $property->getValue($this);
            }
        }

        return $serialized;
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
}