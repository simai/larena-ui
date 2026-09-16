<?php

declare(strict_types=1);

namespace Larena\Ui\Runtime;

use Closure;
use InvalidArgumentException;
use stdClass;

/** Exact pinned Document schema shape gate. Type/slot semantics remain registered owner checks. */
final readonly class FrameworkDocumentSchemaValidator
{
    /** @param array<string,mixed> $schema */
    private function __construct(private array $schema) {}

    public static function fromPinnedSchema(string $path, string $digest): self
    {
        if (!is_file($path) || is_link($path) || !preg_match('/^sha256:[a-f0-9]{64}$/D', $digest)) {
            throw new InvalidArgumentException('ui_document_schema_unavailable');
        }
        $bytes = file_get_contents($path);
        if (!is_string($bytes) || strlen($bytes) > 262144 || !hash_equals($digest, 'sha256:'.hash('sha256', $bytes))) {
            throw new InvalidArgumentException('ui_document_schema_digest_mismatch');
        }
        $schema = json_decode($bytes, true, 64, JSON_THROW_ON_ERROR);
        if (!is_array($schema) || ($schema['$id'] ?? null) !== 'https://simai.io/contracts/composition-document.v1.schema.json'
            || ($schema['$schema'] ?? null) !== 'https://json-schema.org/draft/2020-12/schema') {
            throw new InvalidArgumentException('ui_document_schema_identity_invalid');
        }
        return new self($schema);
    }

    /** @return array<string,mixed> */
    public function document(string $json): array
    {
        if (strlen($json) > 1048576) throw new InvalidArgumentException('ui_document_byte_limit');
        // Keep JSON objects distinct from arrays until schema validation has completed.
        $value = json_decode($json, false, 128, JSON_THROW_ON_ERROR);
        $this->check($value, $this->schema, '$', 0);
        $this->portableFields($value, '$');
        $document = json_decode($json, true, 128, JSON_THROW_ON_ERROR);
        if (!is_array($document)) throw new InvalidArgumentException('ui_document_invalid');
        return $document;
    }

    /** @return Closure(array<string,mixed>):void */
    public function binding(string $validatedJson): Closure
    {
        $expected = $this->document($validatedJson);
        return static function (array $document) use ($expected): void {
            if ($document !== $expected) throw new InvalidArgumentException('ui_document_validated_source_changed');
        };
    }

    /** @param array<string,mixed>|bool $schema */
    private function check(mixed $value, array|bool $schema, string $path, int $depth): void
    {
        if ($depth > 120) throw new InvalidArgumentException('ui_document_schema_depth_limit');
        if (is_bool($schema)) {
            if (!$schema) $this->reject($path, 'schema_false');
            return;
        }
        $supported = ['$schema', '$id', '$defs', 'title', 'description', '$ref', 'type', 'const', 'enum',
            'required', 'properties', 'additionalProperties', 'propertyNames', 'minLength', 'maxLength', 'pattern',
            'items', 'minItems', 'maxItems', 'uniqueItems'];
        foreach (array_keys($schema) as $keyword) {
            if (!in_array($keyword, $supported, true)) $this->reject($path, 'schema_keyword_unsupported');
        }
        if (isset($schema['$ref'])) {
            $ref = $schema['$ref'];
            if (!is_string($ref) || !preg_match('~^#/\$defs/([A-Za-z0-9_-]+)$~D', $ref, $matches)
                || !is_array($this->schema['$defs'][$matches[1]] ?? null)) $this->reject($path, 'schema_ref_unsupported');
            $this->check($value, $this->schema['$defs'][$matches[1]], $path, $depth + 1);
        }
        if (array_key_exists('const', $schema) && $value !== $schema['const']) $this->reject($path, 'schema_const');
        if (isset($schema['enum']) && !in_array($value, $schema['enum'], true)) $this->reject($path, 'schema_enum');
        if (isset($schema['type'])) {
            $matches = match ($schema['type']) {
                'object' => $value instanceof stdClass, 'array' => is_array($value), 'string' => is_string($value),
                'integer' => is_int($value), 'number' => is_int($value) || is_float($value), 'boolean' => is_bool($value), 'null' => $value === null,
                default => throw new InvalidArgumentException('ui_document_schema_type_unsupported'),
            };
            if (!$matches) $this->reject($path, 'schema_type');
        }
        if (is_string($value)) {
            $length = mb_strlen($value, 'UTF-8');
            if (isset($schema['minLength']) && $length < $schema['minLength']) $this->reject($path, 'schema_min_length');
            if (isset($schema['maxLength']) && $length > $schema['maxLength']) $this->reject($path, 'schema_max_length');
            if (isset($schema['pattern'])) {
                $result = preg_match('~'.str_replace('~', '\~', $schema['pattern']).'~u', $value);
                if ($result !== 1) $this->reject($path, 'schema_pattern');
            }
        }
        if (is_array($value)) {
            if (isset($schema['minItems']) && count($value) < $schema['minItems']) $this->reject($path, 'schema_min_items');
            if (isset($schema['maxItems']) && count($value) > $schema['maxItems']) $this->reject($path, 'schema_max_items');
            if (($schema['uniqueItems'] ?? false) && count(array_unique(array_map(
                static fn (mixed $item): string => json_encode($item, JSON_THROW_ON_ERROR), $value))) !== count($value)) {
                $this->reject($path, 'schema_unique_items');
            }
            if (isset($schema['items'])) foreach ($value as $index => $child) $this->check($child, $schema['items'], $path.'['.$index.']', $depth + 1);
        }
        if ($value instanceof stdClass) {
            $fields = get_object_vars($value);
            foreach (($schema['required'] ?? []) as $name) if (!array_key_exists($name, $fields)) $this->reject($path.'.'.$name, 'schema_required');
            foreach ($fields as $name => $child) {
                $name = (string) $name;
                if (isset($schema['propertyNames'])) $this->check($name, $schema['propertyNames'], $path.'.'.$name, $depth + 1);
                if (isset($schema['properties'][$name])) $this->check($child, $schema['properties'][$name], $path.'.'.$name, $depth + 1);
                elseif (array_key_exists('additionalProperties', $schema)) $this->check($child, $schema['additionalProperties'], $path.'.'.$name, $depth + 1);
            }
        }
    }

    private function portableFields(mixed $value, string $path): void
    {
        if (is_array($value)) {
            foreach ($value as $index => $child) $this->portableFields($child, $path.'['.$index.']');
        } elseif ($value instanceof stdClass) {
            $forbidden = ['html','innerhtml','script','javascript','php','eval','function','expression','query','sql','graphql',
                'class','classname','rootclass','cssclass','secret','password','token','cookie','authorization','request'];
            foreach (get_object_vars($value) as $name => $child) {
                $name = (string) $name;
                if (in_array(str_replace('-', '', strtolower($name)), $forbidden, true)
                    || preg_match('/(^|[_.-])(html|css|javascript|js|php|script|callback|callable|password|token|secret|credential|csrf|session|absolute_path|template_path)([_.-]|$)/', strtolower($name)) === 1) {
                    $this->reject($path.'.'.$name, 'executable_or_secret_field_forbidden');
                }
                $this->portableFields($child, $path.'.'.$name);
            }
        }
    }

    private function reject(string $path, string $code): never
    {
        throw new InvalidArgumentException('ui_document_'.$code.':'.$path);
    }
}
