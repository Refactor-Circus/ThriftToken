<?php

namespace RefactorCircus\ThriftToken\Domains\Decoding\Data;

/**
 * One field entry of a header's field list, optionally carrying its own
 * nested field group (spec §6, §9.3).
 */
final readonly class HeaderField
{
    /**
     * @param  list<HeaderField>|null  $children  The nested field group, or null for a leaf field.
     */
    public function __construct(
        public string $name,
        public ?array $children = null,
    ) {}

    /**
     * @param  list<HeaderField>  $fields
     */
    public static function leaves(array $fields): int
    {
        $count = 0;

        foreach ($fields as $field) {
            $count += $field->children === null ? 1 : self::leaves($field->children);
        }

        return $count;
    }
}
