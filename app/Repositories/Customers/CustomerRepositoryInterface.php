<?php

namespace App\Repositories\Customers;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\Customer;

interface CustomerRepositoryInterface
{
    public function getAll(): Collection;

    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function findById(int $id): Customer;

    public function create(array $data);

    public function update(Customer $customer, array $data);

    public function delete(Customer $customer);
}