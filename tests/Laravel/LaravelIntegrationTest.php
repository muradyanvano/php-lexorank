<?php

declare(strict_types=1);

namespace MuradyanVano\LexoRank\Tests\Laravel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use MuradyanVano\LexoRank\Laravel\Casts\LexoRankCast;
use MuradyanVano\LexoRank\Laravel\Concerns\HasLexoRank;
use MuradyanVano\LexoRank\Laravel\Facades\LexoRank as LexoRankFacade;
use MuradyanVano\LexoRank\Laravel\LexoRankServiceProvider;
use MuradyanVano\LexoRank\LexoRank;
use MuradyanVano\LexoRank\LexoRankService;
use MuradyanVano\LexoRank\Rebalancer;
use Orchestra\Testbench\TestCase;

final class LaravelIntegrationTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [LexoRankServiceProvider::class];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'LexoRankFacade' => LexoRankFacade::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function defineDatabaseMigrations(): void
    {
        Schema::create('tasks', static function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('rank', 255)->nullable()->unique();
            $table->timestamps();
        });
    }

    public function test_container_bindings(): void
    {
        self::assertInstanceOf(LexoRankService::class, $this->app->make(LexoRankService::class));
        self::assertInstanceOf(Rebalancer::class, $this->app->make(Rebalancer::class));
        self::assertSame(
            $this->app->make(LexoRankService::class),
            $this->app->make(LexoRankService::class),
        );
    }

    public function test_facade_between_and_initial_ranks(): void
    {
        $ranks = LexoRankFacade::initialRanks(5);
        self::assertCount(5, $ranks);

        $mid = LexoRankFacade::between($ranks[0], $ranks[4]);
        self::assertTrue($ranks[0]->isBefore($mid));
        self::assertTrue($mid->isBefore($ranks[4]));
    }

    public function test_facade_rebalance(): void
    {
        $ranks = LexoRankFacade::initialRanks(3);
        $result = LexoRankFacade::rebalance($ranks);
        self::assertSame(3, $result->count());
        self::assertSame('1', $result->bucket()->toString());
    }

    public function test_cast_get_set_and_null(): void
    {
        $task = new Task();
        $task->title = 'A';
        $task->rank = LexoRank::middle();
        $task->save();

        $fresh = Task::query()->findOrFail($task->id);
        self::assertInstanceOf(LexoRank::class, $fresh->rank);
        self::assertSame('0|hzzzzz:', $fresh->rank->toString());

        $fresh->rank = null;
        $fresh->save();
        self::assertNull($fresh->fresh()->rank);

        $fresh->rank = '0|100000:';
        $fresh->save();
        self::assertSame('0|100000:', $fresh->fresh()->rank->toString());
    }

    public function test_has_lexo_rank_trait_and_ordering(): void
    {
        $a = Task::query()->create(['title' => 'A', 'rank' => LexoRank::middle()->toString()]);
        $b = new Task(['title' => 'B']);
        $b->moveAfter($a);
        $b->save();

        $c = new Task(['title' => 'C']);
        $c->moveBetween($a, $b);
        $c->save();

        $ordered = Task::query()->orderByRank()->get();
        self::assertSame(['A', 'C', 'B'], $ordered->pluck('title')->all());
    }

    public function test_move_before_and_save(): void
    {
        $a = Task::query()->create(['title' => 'A', 'rank' => LexoRank::middle()->toString()]);
        $b = Task::query()->create(['title' => 'B', 'rank' => LexoRank::middle()->after()->toString()]);

        $b->moveBeforeAndSave($a);

        $ordered = Task::query()->orderByRank()->pluck('title')->all();
        self::assertSame(['B', 'A'], $ordered);
    }
}

/**
 * @property int         $id
 * @property string      $title
 * @property LexoRank|null $rank
 */
final class Task extends Model
{
    use HasLexoRank;

    protected $table = 'tasks';

    protected $guarded = [];

    protected $casts = [
        'rank' => LexoRankCast::class,
    ];
}
