<?php

namespace Pterodactyl\Models;

use Schema;
use Illuminate\Notifications\Notifiable;
use Pterodactyl\Models\Traits\Searchable;
use Znck\Eloquent\Traits\BelongsToThrough;

/**
 * @property int $id
 * @property string|null $external_id
 * @property string $uuid
 * @property string $uuidShort
 * @property int $node_id
 * @property string $name
 * @property string $description
 * @property bool $skip_scripts
 * @property int $suspended
 * @property int $owner_id
 * @property int $memory
 * @property int $swap
 * @property int $disk
 * @property int $io
 * @property int $cpu
 * @property bool $oom_disabled
 * @property int $allocation_id
 * @property int $nest_id
 * @property int $egg_id
 * @property int|null $pack_id
 * @property string $startup
 * @property string $image
 * @property int $installed
 * @property int $allocation_limit
 * @property int $database_limit
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property \Pterodactyl\Models\User $user
 * @property \Pterodactyl\Models\User[]|\Illuminate\Support\Collection $subusers
 * @property \Pterodactyl\Models\Allocation $allocation
 * @property \Pterodactyl\Models\Allocation[]|\Illuminate\Support\Collection $allocations
 * @property \Pterodactyl\Models\Pack|null $pack
 * @property \Pterodactyl\Models\Node $node
 * @property \Pterodactyl\Models\Nest $nest
 * @property \Pterodactyl\Models\Egg $egg
 * @property \Pterodactyl\Models\ServerVariable[]|\Illuminate\Support\Collection $variables
 * @property \Pterodactyl\Models\Schedule[]|\Illuminate\Support\Collection $schedule
 * @property \Pterodactyl\Models\Database[]|\Illuminate\Support\Collection $databases
 * @property \Pterodactyl\Models\Location $location
 * @property \Pterodactyl\Models\DaemonKey $key
 * @property \Pterodactyl\Models\DaemonKey[]|\Illuminate\Support\Collection $keys
 */
class Server extends Validable
{
    use BelongsToThrough, Notifiable, Searchable;

    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    const RESOURCE_NAME = 'server';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'servers';

    /**
     * Default values when creating the model. We want to switch to disabling OOM killer
     * on server instances unless the user specifies otherwise in the request.
     *
     * @var array
     */
    protected $attributes = [
        'oom_disabled' => true,
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [self::CREATED_AT, self::UPDATED_AT, 'deleted_at'];

    /**
     * Fields that are not mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id', 'installed', self::CREATED_AT, self::UPDATED_AT, 'deleted_at'];

    /**
     * @var array
     */
    public static $validationRules = [
        'external_id' => 'sometimes|nullable|string|between:1,191|unique:servers',
        'owner_id' => 'required|integer|exists:users,id',
        'name' => 'required|string|min:1|max:255',
        'node_id' => 'required|exists:nodes,id',
        'description' => 'string',
        'memory' => 'required|numeric|min:0',
        'swap' => 'required|numeric|min:-1',
        'io' => 'required|numeric|between:10,1000',
        'cpu' => 'required|numeric|min:0',
        'oom_disabled' => 'sometimes|boolean',
        'disk' => 'required|numeric|min:0',
        'allocation_id' => 'required|bail|unique:servers|exists:allocations,id',
        'nest_id' => 'required|exists:nests,id',
        'egg_id' => 'required|exists:eggs,id',
        'pack_id' => 'sometimes|nullable|numeric|min:0',
        'startup' => 'required|string',
        'skip_scripts' => 'sometimes|boolean',
        'image' => 'required|string|max:255',
        'installed' => 'in:0,1,2',
        'database_limit' => 'present|nullable|integer|min:0',
        'allocation_limit' => 'sometimes|nullable|integer|min:0',
    ];

    /**
     * Cast values to correct type.
     *
     * @var array
     */
    protected $casts = [
        'node_id' => 'integer',
        'skip_scripts' => 'boolean',
        'suspended' => 'integer',
        'owner_id' => 'integer',
        'memory' => 'integer',
        'swap' => 'integer',
        'disk' => 'integer',
        'io' => 'integer',
        'cpu' => 'integer',
        'oom_disabled' => 'boolean',
        'allocation_id' => 'integer',
        'nest_id' => 'integer',
        'egg_id' => 'integer',
        'pack_id' => 'integer',
        'installed' => 'integer',
        'database_limit' => 'integer',
        'allocation_limit' => 'integer',
    ];

    /**
     * Parameters for search querying.
     *
     * @var array
     */
    protected $searchableColumns = [
        'name' => 100,
        'uuid' => 80,
        'uuidShort' => 80,
        'external_id' => 50,
        'user.email' => 40,
        'user.username' => 30,
        'node.name' => 10,
        'pack.name' => 10,
    ];

    /**
     * Return the columns available for this table.
     *
     * @return array
     */
    public function getTableColumns()
    {
        return Schema::getColumnListing($this->getTable());
    }

    /**
     * Returns the format for server allocations when communicating with the Daemon.
     *
     * @return array
     */
    public function getAllocationMappings(): array
    {
        return $this->allocations->groupBy('ip')->map(function ($item) {
            return $item->pluck('port');
        })->toArray();
    }

    /**
     * @return bool
     */
    public function isInstalled(): bool
    {
        return $this->installed === 1;
    }

    /**
     * Gets the user who owns the server.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Gets the subusers associated with a server.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function subusers()
    {
        return $this->hasManyThrough(User::class, Subuser::class, 'server_id', 'id', 'id', 'user_id');
    }

    /**
     * Gets the default allocation for a server.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function allocation()
    {
        return $this->hasOne(Allocation::class, 'id', 'allocation_id');
    }

    /**
     * Gets all allocations associated with this server.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function allocations()
    {
        return $this->hasMany(Allocation::class, 'server_id');
    }

    /**
     * Gets information for the pack associated with this server.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function pack()
    {
        return $this->belongsTo(Pack::class);
    }

    /**
     * Gets information for the nest associated with this server.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function nest()
    {
        return $this->belongsTo(Nest::class);
    }

    /**
     * Gets information for the egg associated with this server.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function egg()
    {
        return $this->belongsTo(Egg::class);
    }

    /**
     * Gets information for the service variables associated with this server.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function variables()
    {
        return $this->hasMany(ServerVariable::class);
    }

    /**
     * Gets information for the node associated with this server.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function node()
    {
        return $this->belongsTo(Node::class);
    }

    /**
     * Gets information for the tasks associated with this server.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function schedule()
    {
        return $this->hasMany(Schedule::class);
    }

    /**
     * Gets all databases associated with a server.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function databases()
    {
        return $this->hasMany(Database::class);
    }

    /**
     * Returns the location that a server belongs to.
     *
     * @return \Znck\Eloquent\Relations\BelongsToThrough
     *
     * @throws \Exception
     */
    public function location()
    {
        return $this->belongsToThrough(Location::class, Node::class);
    }

    /**
     * Return the key belonging to the server owner.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function key()
    {
        return $this->hasOne(DaemonKey::class, 'user_id', 'owner_id');
    }

    /**
     * Returns all of the daemon keys belonging to this server.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function keys()
    {
        return $this->hasMany(DaemonKey::class);
    }
}
