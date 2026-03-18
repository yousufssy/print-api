<?php
namespace App\Http\Controllers;
use App\Models\Customer;
use Illuminate\Http\{Request, JsonResponse};

class CustomerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = $request->get('q', '');
        $query = Customer::query();
        if ($q) $query->where('Customer', 'like', "%$q%");
        return response()->json($query->orderBy('Customer')->limit(200)->get());
    }
    public function store(Request $request): JsonResponse
    {
        $c = Customer::create($request->validate(['Customer'=>'required|string','Activety'=>'nullable|string']));
        return response()->json($c, 201);
    }
    public function show(string $id): JsonResponse   { return response()->json(Customer::findOrFail($id)); }
    public function update(Request $request, string $id): JsonResponse
    {
        $c = Customer::findOrFail($id); $c->update($request->all()); return response()->json($c);
    }
    public function destroy(string $id): JsonResponse { Customer::findOrFail($id)->delete(); return response()->json(['message'=>'Deleted.']); }
}
