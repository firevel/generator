{!! "<\?php" !!}

namespace Database\Seeders;

use App\Models\{{$resource->name()->singular()->studly()}};
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class {{$resource->name()->singular()->studly()}}Seeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        {{$resource->name()->singular()->studly()}}::factory()->count(50)->create();
    }
}
