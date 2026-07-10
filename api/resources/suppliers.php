<?php
/** /api/suppliers — generic CRUD. */

function handle(string $method, ?int $id, ?string $action): void
{
    simple_crud([
        'table'    => 'suppliers',
        'fields'   => ['name','phone','email','address'],
        'required' => ['name'],
        'search'   => ['name'],
    ], $method, $id);
}
