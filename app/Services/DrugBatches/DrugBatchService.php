<?php

namespace App\Services\DrugBatches;

use App\Enums\StockMovementType;
use App\Models\DrugBatch;
use App\Repositories\DrugBatches\DrugBatchRepositoryInterface;
use App\Services\Inventory\InventoryService;
use Illuminate\Support\Facades\DB;

class DrugBatchService
{
    public function __construct(
        protected DrugBatchRepositoryInterface $batchRepo,
        protected InventoryService $inventoryService
    ) {}

    public function getAll()
    {
        return $this->batchRepo->getAll();
    }

    public function paginate(
        int $perPage = 15,
        ?string $search = null,
        ?int $drugId = null
    ) {
        return $this->batchRepo->paginate(
            $perPage,
            $search,
            $drugId
        );
    }

    public function findById(int $id)
    {
        return $this->batchRepo->findById($id);
    }

    public function getByDrug(
        int $drugId,
        int $perPage = 15
    ) {
        return $this->batchRepo->getByDrug(
            $drugId,
            $perPage
        );
    }

    public function create(
        array $data,
        $user = null
    ): DrugBatch {
        return DB::transaction(function () use (
            $data,
            $user
        ) {
            $quantity =
                (int) $data['quantity_received'];

            /*
             * Always start at zero.
             * InventoryService will increase it
             * and create the stock movement.
             */
            $data['quantity_available'] = 0;

            if (empty($data['received_at'])) {
                $data['received_at'] = now();
            }

            $batch = $this->batchRepo->create($data);

            $this->inventoryService->increase(
                drugBatch: $batch,
                quantity: $quantity,
                movementType: StockMovementType::PURCHASE,
                user: $user,
                notes: 'Initial batch stock received.'
            );

            return $this->findById($batch->id);
        });
    }

    public function getSellableBatches(int $drugId)
    {
        return $this->batchRepo
            ->getSellableBatches($drugId);
    }
}