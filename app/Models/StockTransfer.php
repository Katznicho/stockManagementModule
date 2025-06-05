<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockTransfer extends Model
{
    //


    protected $fillable = [
        'from_entity_id',
        'to_entity_id',
        'from_branch_id',
        'to_branch_id',
        'item_id',
        'from_store_id',
        'to_store_id',
        'qty_requested_suom',
        'qty_approved_suom',
        'qty_received_suom',        
        'reason',
        'status',
        'requesting_user_id',
        'approving_user_id',   
        'external_id' 
    ];

    //relationships
    public function fromEntity()
    {
        return $this->belongsTo(Entity::class, 'from_entity_id');
    }
    public function toEntity()
    {
        return $this->belongsTo(Entity::class, 'to_entity_id');
    }
    public function fromBranch()
    {
        return $this->belongsTo(Branch::class, 'from_branch_id');
    }
}
