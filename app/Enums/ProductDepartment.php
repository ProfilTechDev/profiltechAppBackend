<?php

namespace App\Enums;

/**
 * Which warehouse department is responsible for packing a product.
 *
 * Splits packing lists per department so steel plates, flashings, and
 * accessories can be picked in parallel without each department's list
 * mixing them (the mixed-list problem the paper workflow had).
 *
 * Derived from the product's WooCommerce categories at sync time and
 * snapshotted onto each OrderLineProduct so future re-classifications
 * don't rewrite historical packing assignments.
 */
enum ProductDepartment: string
{
    case SteelPlates = 'steel_plates';
    case Flashings = 'flashings';
    case Accessories = 'accessories';

    /**
     * Resolve a product's department from its WooCommerce categories
     * using the priority rules in config/departments.php:
     *   1. SteelPlates wins if any configured steel slug is present
     *      (typically parent slugs `staaltag` / `kliktag`).
     *   2. Else Flashings if `inddaekninger` (or any configured
     *      flashing slug) is present.
     *   3. Else Accessories (catch-all).
     *
     * @param  array<int, array{id?: int, name?: string, slug?: string}>  $categories
     */
    public static function fromCategories(array $categories): self
    {
        $rules = (array) config('departments.rules', []);

        $slugs = array_filter(array_map(
            static fn (array $category): string => (string) ($category['slug'] ?? ''),
            $categories,
        ));

        foreach ([self::SteelPlates, self::Flashings] as $candidate) {
            $configuredSlugs = (array) ($rules[$candidate->value] ?? []);

            foreach ($configuredSlugs as $slug) {
                if (in_array($slug, $slugs, true)) {
                    return $candidate;
                }
            }
        }

        return self::Accessories;
    }
}
