<?php
namespace App\Http\Controllers;

use Illuminate\Http\{Request, JsonResponse};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProblemcuController extends Controller
{
    private const ALLOWED_FIELDS = [
        'ID', 'year', 'ta3lel', 'tlf', 'err', 'incre', 'dn'
    ];

    private function cleanInput(array $data): array
    {
        return array_intersect_key($data, array_flip(self::ALLOWED_FIELDS));
    }

    // GET /api/problemcu
    public function index(Request $request): JsonResponse
    {
        try {
            $year = $request->get('year');
            $id   = $request->get('ID');

            $query = DB::table('problemcu')
                ->when($year, fn($q) => $q->where('year', $year))
                ->when($id, fn($q) => $q->where('ID', $id));

            return response()->json($query->get());

        } catch (\Exception $e) {
            Log::error('problemcu index error', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // POST /api/problemcu
    public function store(Request $request): JsonResponse
    {
        try {
            $data = $this->cleanInput($request->all());

            if (empty($data['ID']) || empty($data['year'])) {
                return response()->json(['error' => 'Missing ID or year'], 422);
            }

            DB::table('problemcu')->insert($data);

            return response()->json(['message' => 'Created'], 201);

        } catch (\Exception $e) {
            Log::error('problemcu store error', [
                'message' => $e->getMessage(),
                'input'   => $request->all()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // GET /api/problemcu/{id}/{year}
    public function show(string $id, string $year): JsonResponse
    {
        try {
            $record = DB::table('problemcu')
                ->where('ID', $id)
                ->where('year', $year)
                ->first();

            return response()->json(
                $record ?: ['error' => 'Not found'],
                $record ? 200 : 404
            );

        } catch (\Exception $e) {
            Log::error('problemcu show error', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // PUT /api/problemcu/{id}/{year}
    public function update(Request $request, string $id, string $year): JsonResponse
    {
        try {
            $data = $this->cleanInput($request->all());

            // لا نسمح بتعديل المفتاح
            unset($data['ID'], $data['year']);

            if (empty($data)) {
                return response()->json(['error' => 'No valid fields to update'], 422);
            }

            $affected = DB::table('problemcu')
                ->where('ID', $id)
                ->where('year', $year)
                ->update($data);

            return response()->json(
                ['message' => $affected ? 'Updated' : 'Not found'],
                $affected ? 200 : 404
            );

        } catch (\Exception $e) {
            Log::error('problemcu update error', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // DELETE /api/problemcu/{id}/{year}
    public function destroy(string $id, string $year): JsonResponse
    {
        try {
            $affected = DB::table('problemcu')
                ->where('ID', $id)
                ->where('year', $year)
                ->delete();

            return response()->json(
                ['message' => $affected ? 'Deleted' : 'Not found'],
                $affected ? 200 : 404
            );

        } catch (\Exception $e) {
            Log::error('problemcu delete error', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
