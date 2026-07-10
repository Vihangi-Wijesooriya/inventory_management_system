<?php
/** /api/categories — generic CRUD. */

function handle(string $method, ?int $id, ?string $action): void
{
    simple_crud([
        'table'    => 'categories',
        'fields'   => ['name','description'],
        'required' => ['name'],
        'search'   => ['name'],
    ], $method, $id);
}
