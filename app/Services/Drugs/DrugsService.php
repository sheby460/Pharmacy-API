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

    public function paginate(
        int $perPage = 15,
        ?string $search = null
    ) {
        return $this->drugRepo->paginate(
            $perPage,
            $search
        );
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
            $barcode = 'DRG' . random_int(
                10000000,
                99999999
            );
        } while (
            Drug::where('barcode', $barcode)->exists()
        );

        return $barcode;
    }

    public function update(
        int $id,
        array $data
    ) {
        $drug = $this->findById($id);

        if (
            empty($data['barcode']) &&
            empty($drug->barcode)
        ) {
            $data['barcode'] =
                $this->generateUniqueBarcode();
        }

        return $this->drugRepo->update(
            $drug,
            $data
        );
    }

    public function delete(int $id)
    {
        $drug = $this->findById($id);

        /*
         * Do not allow deletion if stock exists.
         */
        $stock = $drug->batches()
            ->sum('quantity_available');

        if ($stock > 0) {
            throw new \RuntimeException(
                'This drug cannot be deleted while stock is available.'
            );
        }

        return $this->drugRepo->delete($drug);
    }
}