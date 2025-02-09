<?php

namespace Pterodactyl\Repositories\Wings;

use BadMethodCallException;
use Webmozart\Assert\Assert;
use Pterodactyl\Models\Server;
use GuzzleHttp\Exception\TransferException;
use Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException;

class DaemonServerRepository extends DaemonRepository
{
    /**
     * Returns details about a server from the Daemon instance.
     *
     * @return array
     * @throws \Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException
     */
    public function getDetails(): array
    {
        Assert::isInstanceOf($this->server, Server::class);

        try {
            $response = $this->getHttpClient()->get(
                sprintf('/api/servers/%s', $this->server->uuid)
            );
        } catch (TransferException $exception) {
            throw new DaemonConnectionException($exception);
        }

        return json_decode($response->getBody()->__toString(), true);
    }

    /**
     * Creates a new server on the Wings daemon.
     *
     * @param array $data
     *
     * @throws \Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException
     */
    public function create(array $data): void
    {
        Assert::isInstanceOf($this->server, Server::class);

        try {
            $this->getHttpClient()->post(
                '/api/servers', [
                    'json' => $data,
                ]
            );
        } catch (TransferException $exception) {
            throw new DaemonConnectionException($exception);
        }
    }

    /**
     * Updates details about a server on the Daemon.
     *
     * @param array $data
     *
     * @throws \Pterodactyl\Exceptions\Http\Connection\DaemonConnectionException
     */
    public function update(array $data): void
    {
        Assert::isInstanceOf($this->server, Server::class);

        try {
            $this->getHttpClient()->patch('/api/servers/' . $this->server->uuid, ['json' => $data]);
        } catch (TransferException $exception) {
            throw new DaemonConnectionException($exception);
        }
    }

    /**
     * Delete a server from the daemon.
     */
    public function delete(): void
    {
        throw new BadMethodCallException('Method is not implemented.');
    }

    /**
     * Reinstall a server on the daemon.
     */
    public function reinstall(): void
    {
        throw new BadMethodCallException('Method is not implemented.');
    }

    public function suspend(): void
    {
        throw new BadMethodCallException('Method is not implemented.');
    }

    public function unsuspend(): void
    {
        throw new BadMethodCallException('Method is not implemented.');
    }
}
