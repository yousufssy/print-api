<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Order extends Model
{
    protected $table      = 'MasterW';
    protected $primaryKey = 'ID';
    public    $timestamps = false;

   protected $fillable = [
     'Ser', 'ID', 'Customer', 'date_come', 'Agre_Date', 'Apoent_Delv_date', 'delev_date', 'Perioud',
    'Demand', 'Med_smpl_Q', 'Qunt_Dlv', 'unit', 'Pattern', 'Pattern2', 'ear', 'UnitMed', 'Form', 'ExplForm',
    'Loading', 'Notes1', 'Code_M', 'authorization', 'Price', 'Free_txt', 'Free_clr', 'Code', 'Mix_num', 'ProDate',
    'ExpDate', 'Authr_co', 'Eng_Name', 'Pat_Num', 'SoftU', 'TafU', 'LongU', 'WedthU', 'HightU', 'Lesan', 'MontagNum',
    'Cut_Num', 'modefyM', 'DubelM', 'Num_comp', 'note_ord', 'final_size_tall', 'final_size_width',
    'final_size_tall2', 'final_size_width2', 'print_on', 'print_on2', 'sheet_unit_qunt', 'sheet_unit_qunt2',
    'Clr_qunt', 'Varnish', 'grnd_qunt', 'Qunt_of_print_on', 'Qunt_of_print_on2', 'Med_Sampel', 'Cus_Paking',
    'box_stk_typ', 'Proplems_Pro', 'Proplems_Cus', 'Notes', 'Reseved', 'Billed', 'Year', 'Activity', 'location',
    'Machin_Print', 'Machin_Cut', 'varnich', 'uv_Spot', 'uv', 'seluvan_lum', 'seluvan_mat', 'tabkha', 'Tay',
    'Tad3em', 'harary', 'bals', 'marji3', 'clr_Qnt_order', 'cut1', 'cut2', 'Label_Price', 'Repar_Wages',
    'Print_Value', 'Other', 'Label_Price1', 'Equation', 'Currency', 'x_Price', 'rolling', 'rollingBack', 'Printed',
    'AttachmentsOrders', 'OldID', 'OldYear'
    ];

    // ── Scopes ──────────────────────────────────────────
    public function scopeForyear(Builder $q, string $Year): Builder
    {
        return $q->where('Year', $Year);
    }

    public function scopeSearch(Builder $q, string $term): Builder
    {
        return $q->where(function ($q) use ($term) {
            $q->where('ID',       'like', "%$term%")
              ->orWhere('Customer','like', "%$term%")
              ->orWhere('Eng_Name','like', "%$term%");
        });
    }

    // ── Relations ───────────────────────────────────────
    public function vouchers()
    {
        return $this->hasMany(Voucher::class, 'ID', 'ID')
                    ->where('Year', $this->Year);
    }
}
