<?php
namespace App\Repositories\Customers;           
use App\Models\Customer;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class CustomerRepository implements CustomerRepositoryInterface
{
    public function getAll(): Collection
    {
        return Customer::query()->latest()->get();
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Customer::query()->latest()->paginate($perPage);
    }

    public function findById(int $id): Customer
    {
        return Customer::query()->findOrFail($id);
    }

    public function create(array $data)
    {
        return Customer::create($data);
    }

    public function update(Customer $customer, array $data): Customer
    {
        $customer->update($data);
        return $customer->refresh();
    }

    public function delete(Customer $customer): bool
    {
        return $customer->delete();
    }
}