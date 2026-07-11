<?php

namespace App\Services\CustomerService;
use App\Repositories\Customers\CustomerRepositoryInterface;

class CustomerService
{
    public function __construct(
        protected CustomerRepositoryInterface $customerRepository
    )
    {      

    }

    public function getAllCustomers()
    {
        return $this->customerRepository->getAll();
    }

    public function paginateCustomers(int $perPage = 15)
    {
        return $this->customerRepository->paginate($perPage);
    }

    public function getCustomerById(int $id)
    {
        return $this->customerRepository->findById($id);
    }

    public function createCustomer(array $data)
    {
        return $this->customerRepository->create($data);
    }

    public function updateCustomer(int $id, array $data)
    {
        $customer = $this->customerRepository->findById($id);
        return $this->customerRepository->update($customer, $data);
    }

    public function deleteCustomer(int $id)
    {
        $customer = $this->customerRepository->findById($id);
        return $this->customerRepository->delete($customer);
    }
}
