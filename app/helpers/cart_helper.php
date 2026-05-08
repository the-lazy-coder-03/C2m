<?php

require_once __DIR__ . '/session_helper.php';

const CART_SESSION_KEY = 'cart_product_ids';

if (!function_exists('cart_product_ids')) {
    /**
     * Return the unique product IDs currently stored in the user's session cart.
     */
    function cart_product_ids(): array
    {
        startUserSession();

        $cartIds = $_SESSION[CART_SESSION_KEY] ?? [];

        if (!is_array($cartIds)) {
            return [];
        }

        $cleanIds = array_map('intval', $cartIds);
        $cleanIds = array_filter($cleanIds, static fn (int $id): bool => $id > 0);

        return array_values(array_unique($cleanIds));
    }
}

if (!function_exists('cart_item_count')) {
    /**
     * Count products in the session cart.
     */
    function cart_item_count(): int
    {
        return count(cart_product_ids());
    }
}

if (!function_exists('cart_add_product')) {
    /**
     * Add a product to the cart once. This marketplace sells one listing at a time.
     */
    function cart_add_product(int $productId): void
    {
        startUserSession();

        $cartIds = cart_product_ids();

        if (!in_array($productId, $cartIds, true)) {
            $cartIds[] = $productId;
        }

        $_SESSION[CART_SESSION_KEY] = $cartIds;
    }
}

if (!function_exists('cart_remove_product')) {
    /**
     * Remove one product from the cart.
     */
    function cart_remove_product(int $productId): void
    {
        startUserSession();

        $_SESSION[CART_SESSION_KEY] = array_values(array_filter(
            cart_product_ids(),
            static fn (int $id): bool => $id !== $productId
        ));
    }
}

if (!function_exists('cart_clear')) {
    /**
     * Empty the session cart after a successful checkout.
     */
    function cart_clear(): void
    {
        startUserSession();
        unset($_SESSION[CART_SESSION_KEY]);
    }
}
