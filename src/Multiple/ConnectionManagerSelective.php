<?php

namespace ConnectionManager\Extra\Multiple;

use React\SocketClient\ConnectorInterface;
use React\Promise;
use UnderflowException;
use InvalidArgumentException;

class ConnectionManagerSelective implements ConnectorInterface
{
    private $managers;

    /**
     *
     * @param ConnectorInterface[] $managers
     */
    public function __construct(array $managers)
    {
        foreach ($managers as $filter => $manager) {
            $host = $filter;
            $portMin = 0;
            $portMax = 65535;

            // search colon (either single one OR preceded by "]" due to IPv6)
            $colon = strrpos($host, ':');
            if ($colon !== false && (strpos($host, ':') === $colon || substr($host, $colon - 1, 1) === ']' )) {
                if (!isset($host[$colon + 1])) {
                    throw new InvalidArgumentException('Entry "' . $filter . '" has no port after colon');
                }

                $minus = strpos($host, '-', $colon);
                if ($minus === false) {
                    $portMin = $portMax = (int)substr($host, $colon + 1);

                    if (substr($host, $colon + 1) !== (string)$portMin) {
                        throw new InvalidArgumentException('Entry "' . $filter . '" has no valid port after colon');
                    }
                } else {
                    $portMin = (int)substr($host, $colon + 1, ($minus - $colon));
                    $portMax = (int)substr($host, $minus + 1);

                    if (substr($host, $colon + 1) !== ($portMin . '-' . $portMax)) {
                        throw new InvalidArgumentException('Entry "' . $filter . '" has no valid port range after colon');
                    }

                    if ($portMin > $portMax) {
                        throw new InvalidArgumentException('Entry "' . $filter . '" has port range mixed up');
                    }
                }
                $host = substr($host, 0, $colon);
            }

            if ($host === '') {
                throw new InvalidArgumentException('Entry "' . $filter . '" has an empty host');
            }

            if (!$manager instanceof ConnectorInterface) {
                throw new InvalidArgumentException('Entry "' . $filter . '" is not a valid connector');
            }
        }

        $this->managers = $managers;
    }

    public function create($host, $port)
    {
        try {
            $connector = $this->getConnectorForTarget($host, $port);
        } catch (UnderflowException $e) {
            return Promise\reject($e);
        }
        return $connector->create($host, $port);
    }

    private function getConnectorForTarget($targetHost, $targetPort)
    {
        foreach ($this->managers as $host => $connector) {
            $portMin = 0;
            $portMax = 65535;

            // search colon (either single one OR preceded by "]" due to IPv6)
            $colon = strrpos($host, ':');
            if ($colon !== false && (strpos($host, ':') === $colon || substr($host, $colon - 1, 1) === ']' )) {
                $minus = strpos($host, '-', $colon);
                if ($minus === false) {
                    $portMin = $portMax = (int)substr($host, $colon + 1);
                } else {
                    $portMin = (int)substr($host, $colon + 1, ($minus - $colon));
                    $portMax = (int)substr($host, $minus + 1);
                }
                $host = trim(substr($host, 0, $colon), '[]');
            }

            if ($targetPort >= $portMin && $targetPort <= $portMax && fnmatch($host, $targetHost)) {
                return $connector;
            }
        }

        throw new UnderflowException('No connector for given target found');
    }
}
