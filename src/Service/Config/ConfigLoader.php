<?php
declare(strict_types=1);

namespace DR\GitCommitNotification\Service\Config;

use DR\GitCommitNotification\Entity\Config\Configuration;
use DR\GitCommitNotification\Exception\ConfigException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\SerializerInterface;

class ConfigLoader
{
    private ConfigPathResolver  $pathResolver;
    private SerializerInterface $serializer;
    private ConfigValidator     $validator;
    private LoggerInterface     $log;

    public function __construct(LoggerInterface $log, ConfigPathResolver $pathResolver, ConfigValidator $validator, SerializerInterface $serializer)
    {
        $this->log          = $log;
        $this->pathResolver = $pathResolver;
        $this->serializer   = $serializer;
        $this->validator    = $validator;
    }

    /**
     * @throws ConfigException
     */
    public function load(InputInterface $input): Configuration
    {
        // find config path
        $configPath = $this->pathResolver->resolve($input);

        $this->log->info(sprintf('Using config `%s`', $configPath->getPathname()));

        // validate config
        $configXml = (string)file_get_contents($configPath->getPathname());
        try {
            $this->validator->validate($configXml);
        } catch (ConfigException $e) {
            throw new ConfigException(sprintf('[%s] %s', $configPath->getPathname(), $e->getMessage()));
        }

        $this->log->info(sprintf('Config `%s` is successfully validated.', $configPath->getPathname()));

        // deserialize config
        /** @var Configuration $config */
        $config = $this->serializer->deserialize($configXml, Configuration::class, XmlEncoder::FORMAT);

        // log which configuration was loaded
        $this->log->info(sprintf('Config `%s` successfully deserialized. Found %d rules.', $configPath->getPathname(), count($config->getRules())));

        // bind rule to the config
        foreach ($config->getRules() as $rule) {
            $this->log->info(sprintf('Config rules loaded: %s', $rule->name));
        }

        return $config;
    }
}
