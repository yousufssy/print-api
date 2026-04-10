use App\Models\Carton;

class CartonController extends Controller
{
    public function index()
    {
        return Carton::all();
    }

    public function store(Request $request)
    {
        return Carton::create($request->all());
    }
}
