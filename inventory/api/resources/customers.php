<?php
/** /api/customers — generic CRUD. */

function handle(string $method, ?int $id, ?string $action): void
{
    simple_crud([
        'table'    => 'customers',
        'fields'   => ['name','phone','email','address'],
        'required' => ['name'],
        'search'   => ['name'],
    ], $method, $id);
}
