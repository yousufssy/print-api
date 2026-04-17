<?php
namespace App\Http\Controllers;

use Illuminate\Http\{Request, JsonResponse};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CartonController extends Controller
{
    private const ALLOWED_FIELDS = [
        'ID', 'Year', 'Type1', 'Id_carton', 'Source1', 'Supplier1',
        'Long1', 'Width1', 'Gramage1', 'Sheet_count1',
        'Out_Date', 'Out_ord_num', 'note_crt', 'Price'
    ];

    private function cleanInput(array $data): array
    {
        $clean = array_diff_key($data, array_flip(['_isNew', '_rowId', 'ID1']));

        if (isset($clean['Year']) && !isset($clean['Year'])) {
            $clean['Year'] = $clean['Year'];
            unset($clean['Year']);
        }

        return array_intersect_key($clean, array_flip(self::ALLOWED_FIELDS));
    }

    // GET /api/cartons
    public function index(Request $request): JsonResponse
    {
        try {
            $Year    = $request->get('Year', date('Y'));
            $orderId = $request->get('ID');

            $query = DB::table('Carton')
                ->where('Year', $Year)
                ->when($orderId, fn($q) => $q->where('ID', $orderId));

            return response()->json(
                $query->orderBy('ID1')->get()
            );

        } catch (\Exception $e) {
            Log::error('Cartons index error', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // POST /api/cartons
    public function store(Request $request): JsonResponse
    {
        try {
            $data = $this->cleanInput($request->all());

            if (empty($data['ID']) || empty($data['Year'])) {
                return response()->json(['error' => 'Missing ID or Year'], 422);
            }

            $maxId       = DB::table('Carton')->max('ID1') ?? 0;
            $data['ID1'] = $maxId + 1;

            $id = DB::table('Carton')->insertGetId($data, 'ID1');

            return response()->json(['ID1' => $id, 'ID' => $data['ID']], 201);

        } catch (\Exception $e) {
            Log::error('Cartons store error', [
                'message' => $e->getMessage(),
                'input'   => $request->all()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // GET /api/cartons/{id}
    public function show(string $id): JsonResponse
    {
        try {
            $record = DB::table('Carton')->where('ID1', $id)->first();

            return response()->json(
                $record ?: ['error' => 'Not found'],
                $record ? 200 : 404
            );

        } catch (\Exception $e) {
            Log::error('Cartons show error', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // PUT /api/cartons/{id}
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $data = $this->cleanInput($request->all());
            unset($data['ID'], $data['Year']);

            if (empty($data)) {
                return response()->json(['error' => 'No valid fields to update'], 422);
            }

            $affected = DB::table('Carton')
                ->where('ID1', $id)
                ->update($data);

            return response()->json(
                ['message' => $affected ? 'Updated' : 'Not found'],
                $affected ? 200 : 404
            );

        } catch (\Exception $e) {
            Log::error('Cartons update error', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // DELETE /api/cartons/{id}
    public function destroy(string $id): JsonResponse
    {
        try {
            $affected = DB::table('Carton')
                ->where('ID1', $id)
                ->delete();

            return response()->json(
                ['message' => $affected ? 'Deleted' : 'Not found'],
                $affected ? 200 : 404
            );

        } catch (\Exception $e) {
            Log::error('Cartons destroy error', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
