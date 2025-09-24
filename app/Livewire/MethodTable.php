<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Method;

class MethodTable extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public function render()
    {
        return view('livewire.method-table', [
            'methods' => Method::with('station')->paginate(5),
        ]);
    }
}
