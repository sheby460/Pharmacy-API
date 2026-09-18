<?php

namespace App\Services\Drugs;

use App\Models\Drug;
use App\Repositories\Drugs\DrugsRepositoryInterface;

class DrugsService
{

    public function __construct(
        protected DrugsRepositoryInterface $drugRepo
    ) {}

    public function getAll()
    {
        return $this->drugRepo->getAll();
    }

    public function paginate(int $perPage = 15)
    {
        return $this->drugRepo->paginate($perPage);
    }

    public function findById(int $id)
    {
        return $this->drugRepo->findById($id);
    }

    public function create(array $data)
    {
        if (empty($data['barcode'])) {
            $data['barcode'] = $this->generateUniqueBarcode();
        }
        return $this->drugRepo->create($data);
    }

    protected function generateUniqueBarcode(): string
    {
        do {
            // Format: DRG + 8 digits (example: DRG00482931)
            $barcode = 'DRG' . str_pad(random_int(1, 999999999999), 8, '0', STR_PAD_LEFT);
        } while (Drug::where('barcode', $barcode)->exists());

        return $barcode;
    }

    public function update(int $id, array $data)
    {
        $drug = $this->findById($id);
         // Optional: regenerate only if barcode is empty
        if (empty($data['barcode']) && empty($drug->barcode)) {
            $data['barcode'] = $this->generateUniqueBarcode();
        }
        return $this->drugRepo->update($drug, $data);
    }

    public function delete(int $id)
    {
        $drug = $this->findById($id);
        return $this->drugRepo->delete($drug);
    }
}
