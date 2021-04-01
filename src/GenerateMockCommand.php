<?php

namespace Bytes\MockGenerator;

use Bytes\CommandBundle\Command\BaseCommand;
use Bytes\Common\Faker\Providers\Discord;
use Bytes\Common\Faker\Providers\MiscProvider;
use Bytes\DiscordResponseBundle\Services\DiscordDatetimeInterface;
use Bytes\Tests\Common\TestSerializerTrait;
use Exception;
use Faker\Factory;
use Faker\Generator as FakerGenerator;
use Faker\Provider\Internet;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Filesystem\Filesystem;
use function Symfony\Component\String\u;


/**
 * Class GenerateMockCommand
 * @package Bytes\MockGenerator
 */
class GenerateMockCommand extends BaseCommand
{
    use TestSerializerTrait;

    /**
     * @var string
     */
    protected static $defaultName = 'app:generate:mocks';

    /**
     * @var string
     */
    protected static $defaultDescription = 'Generate mock Discord JSON responses';

    /**
     * @var string[]
     */
    protected static $mocks = [
        'getChannelV6',
        'getChannelV8',
        'getChannelsV6',
        'getChannelsV8',
        'getChannelMessage',
        'getChannelMessages',
        'getGuildMember',
        'getGuildRoles',
        'createGuildRole',
        'getReactions',
    ];

    /**
     * @var string
     */
    private $outputPath;

    /**
     *
     */
    protected function configure()
    {
        parent::configure();
        $this
            ->setDescription(self::$defaultDescription)
            ->addArgument('mocks', InputArgument::IS_ARRAY, sprintf('Mocks to include (e.g. <fg=yellow>%s</>)', implode(', ', self::$mocks)))
            ->addOption('destination', 'd', InputOption::VALUE_REQUIRED, 'Optional output path');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $questions = [];

        $mocks = $input->getArgument('mocks');
        if (!$mocks) {
            $question = new ChoiceQuestion(
                'Select one (or more) mock(s) [comma delimited]:',
                // choices can also be PHP objects that implement __toString() method
                self::$mocks,
                implode(',', range(0, count(self::$mocks) - 1))
            );
            $question->setMultiselect(true);

            $helper = $this->getHelper('question');

            $answer = $helper->ask($input, $output, $question);
            $output->writeln('You have just selected: ' . implode(', ', $answer));

            $mocks = implode(', ', $answer);
        }

        $methods = [];
        if (is_string($mocks)) {
            $mocks = explode(',', $mocks);
        }
        foreach ($mocks as $method) {
            $method = u($method)->trim()->toString();
            if (in_array($method, self::$mocks)) {
                $methods[] = $method;
            }
        }
        $input->setArgument('mocks', implode(',', $methods));
    }

    /**
     * @return int
     */
    protected function executeCommand(): int
    {
        $this->outputPath = u($this->input->getOption('destination') ?? __DIR__ . '/../data/')->ensureEnd(DIRECTORY_SEPARATOR)->toString();

        $mocks = explode(',', $this->input->getArgument('mocks'));

        /** @var FakerGenerator|Discord|MiscProvider $faker */
        $faker = Factory::create();
        $faker->addProvider(new Discord($faker));

        $fs = new Filesystem();
        $serializer = $this->createSerializer();

        //Get Channels v8
        if (in_array('getChannelsV8', $mocks)) {
            $this->io->title('Get Channels V8');
            foreach (range(1, 3) as $k) {
                $channel = $this->generateChannelV8($faker);
                $channels[] = $channel;

                foreach (range(1, 3) as $q) {
                    $channels[] = $this->generateChannelV8($faker, $channel['id']);
                }
            }
            // Turning off SKIP_NULL_VALUES for now
            // [AbstractObjectNormalizer::SKIP_NULL_VALUES => true, 'json_encode_options' => JSON_PRETTY_PRINT]
            $fs->dumpFile($this->outputPath . 'get-channels-v8-success.json', $serializer->serialize($channels, 'json', ['json_encode_options' => JSON_PRETTY_PRINT]));
            $this->io->success('Finished');
        }

        //Get Channels v6
        if (in_array('getChannelsV6', $mocks)) {
            $this->io->title('Get Channels V6');
            foreach (range(1, 3) as $k) {
                $channel = $this->generateChannelV8($faker);
                $channels[] = $channel;

                foreach (range(1, 3) as $q) {
                    $channels[] = $this->generateChannelV6($faker, $channel['id']);
                }
            }
            $fs->dumpFile($this->outputPath . 'get-channels-v6-success.json', $serializer->serialize($channels, 'json', ['json_encode_options' => JSON_PRETTY_PRINT]));
            $this->io->success('Finished');
        }

        // Get Channel v8
        if (in_array('getChannelV8', $mocks)) {
            $this->io->title('Get Channel V8');
            $fs->dumpFile($this->outputPath . 'get-channel-v8-success.json', $serializer->serialize($this->generateChannelV8($faker, $faker->optional()->channelId()), 'json', ['json_encode_options' => JSON_PRETTY_PRINT]));
            $this->io->success('Finished');
        }

        // Get Channel v6
        if (in_array('getChannelV6', $mocks)) {
            $this->io->title('Get Channel V6');
            $this->io->comment($this->outputPath . 'get-channel-v6-success.json');
            $fs->dumpFile($this->outputPath . 'get-channel-v6-success.json', $serializer->serialize($this->generateChannelV6($faker, $faker->optional()->channelId()), 'json', ['json_encode_options' => JSON_PRETTY_PRINT]));
            $this->io->success('Finished');
        }

        // Get Channel Message
        if (in_array('getChannelMessage', $mocks)) {
            $this->io->title('Get Channel Message');
            $fs->dumpFile($this->outputPath . 'get-channel-message-success.json', $serializer->serialize($this->generateChannelMessage($faker), 'json', ['json_encode_options' => JSON_PRETTY_PRINT]));
            $this->io->success('Finished');
        }

        // Get Channel Messages
        if (in_array('getChannelMessages', $mocks)) {
            $this->io->title('Get Channel Messages');
            foreach (range(1, 3) as $k) {
                $messages[] = $this->generateChannelMessage($faker);
            }
            $fs->dumpFile($this->outputPath . 'get-channel-messages-success.json', $serializer->serialize($messages, 'json', ['json_encode_options' => JSON_PRETTY_PRINT]));
            $this->io->success('Finished');
        }

        // Get Guild Member
        if (in_array('getGuildMember', $mocks)) {
            $this->io->title('Get Guild Member');
            $fs->dumpFile($this->outputPath . 'get-guild-member-success.json', $serializer->serialize($this->generateGuildMember($faker), 'json', ['json_encode_options' => JSON_PRETTY_PRINT]));
            $this->io->success('Finished');
        }

        // Get Guild Roles
        if (in_array('getGuildRoles', $mocks)) {
            $this->io->title('Get Guild Roles');
            $roles = [
                $this->generateRole($faker, $faker->guildId())
            ];
            foreach ($faker->rangeBetween(10, 1) as $index) {
                $roles[] = $this->generateRole($faker);
            }
            $fs->dumpFile($this->outputPath . 'get-guild-roles-success.json', $serializer->serialize($roles, 'json', ['json_encode_options' => JSON_PRETTY_PRINT]));
            $this->io->success('Finished');
        }

        // Create Guild Role
        if (in_array('createGuildRole', $mocks)) {
            $this->io->title('Create Guild Role');
            $fs->dumpFile($this->outputPath . 'create-guild-role-success.json', $serializer->serialize($this->generateRole($faker, null, true), 'json', ['json_encode_options' => JSON_PRETTY_PRINT]));
            $this->io->success('Finished');
        }

        // Get Reactions
        if (in_array('getReactions', $mocks)) {
            $this->io->title('Get Reactions');
            foreach ($faker->rangeBetween(10, 1) as $index) {
                $reactions[] = $this->generateUser($faker, true);
            }
            $fs->dumpFile($this->outputPath . 'get-reactions-success.json', $serializer->serialize($reactions, 'json', ['json_encode_options' => JSON_PRETTY_PRINT]));
            $this->io->success('Finished');
        }

        return self::SUCCESS;

    }

    /**
     * @param FakerGenerator|Discord $faker
     * @param string|null $parentId
     * @return array
     */
    protected function generateChannelV8($faker, ?string $parentId = null)
    {
        foreach (range(1, 3) as $i) {
            $overwrite = $this->generateOverwriteV8($faker);
            $overwrites[] = $overwrite;
        }

        $channel = [
            'id' => $faker->channelId(),
            'type' => $faker->channelType(),
            'guild_id' => $faker->guildId(),
            'position' => $faker->randomDigit(),
            'permission_overwrites' => $overwrites,
            'name' => $faker->guildName(),
            'topic' => $faker->optional(0.8)->text(1024),
            'nsfw' => $faker->boolean(),
            'last_message_id' => $faker->optional()->snowflake(),
            'bitrate' => $faker->numberBetween(0, 384000),
            'user_limit' => $faker->randomDigit(),
            'rate_limit_per_user' => $faker->optional()->numberBetween(0, 21600),
            //'recipients' => null,
            'icon' => $faker->optional(0.1)->randomElement([$faker->iconHash(), $faker->iconHash(true)]),
            //'owner_id' => $faker->userId(),
            //'application_id' => $faker->snowflake(),
            'parent_id' => $parentId,
            'last_pin_timestamp' => $faker->timestamp(0.5),
            'rtc_region' => $faker->rtcRegion(),
        ];

        return $channel;
    }

    /**
     * @param FakerGenerator|Discord $faker
     * @return array
     */
    protected function generateOverwriteV8($faker): array
    {
        $overwrite = [
            'id' => $faker->snowflake(),
            'type' => $faker->randomElement(['role', 'member']),
            'allow' => $faker->randomElement([(string)$faker->permissionInteger(), '6546771529']),
            'deny' => $faker->randomElement([(string)$faker->permissionInteger(), '6546771529']),
        ];
        return $overwrite;
    }

    /**
     * @param FakerGenerator|Discord $faker
     * @param string|null $parentId
     * @return array
     */
    protected function generateChannelV6($faker, ?string $parentId = null)
    {
        foreach (range(1, 3) as $i) {
            $overwrite = $this->generateOverwriteV6($faker);
            $overwrites[] = $overwrite;
        }

        $channel = [
            'id' => $faker->channelId(),
            'type' => $faker->channelType(),
            'name' => $faker->guildName(),
            'position' => $faker->randomDigit(),
            'parent_id' => $parentId,
            'bitrate' => $faker->numberBetween(0, 384000),
            'user_limit' => $faker->randomDigit(),
            'rtc_region' => $faker->rtcRegion(),
            'guild_id' => $faker->guildId(),
            'permission_overwrites' => $overwrites,
            'nsfw' => $faker->boolean(),
        ];

        return $channel;
    }

    /**
     * @param FakerGenerator|Discord $faker
     * @return array
     */
    protected function generateOverwriteV6($faker): array
    {
        $overwrite = [
            'id' => $faker->snowflake(),
            'type' => $faker->randomElement(['role', 'member']),
            'allow' => $faker->permissionInteger(),
            'deny' => $faker->permissionInteger(),
        ];
        $overwrite['allowNew'] = $faker->randomElement([(string)$overwrite['allow'], '6546771529']);
        $overwrite['denyNew'] = $faker->randomElement([(string)$overwrite['deny'], '6546771529']);
        return $overwrite;
    }

    /**
     * @param FakerGenerator|Discord|Internet $faker
     * @return array
     * @throws Exception
     */
    protected function generateChannelMessage($faker)
    {
        return [
            'id' => $faker->userId(),
            'type' => $faker->valid(function ($type) {
                return $type === 0 || $type === 19;
            })->messageType(),
            'content' => $faker->sentence(),
            'channel_id' => $faker->channelId(),
            //guild_id
            'author' => $this->generateUser($faker),
            //member
            'attachments' => [],
            'embeds' => [],
            'mentions' => [],
            'mention_roles' => [],
            'pinned' => $faker->boolean(),
            'mention_everyone' => $faker->boolean(),
            'tts' => $faker->boolean(),
            'timestamp' => $faker->timestamp(),
            'edited_timestamp' => $faker->timestamp(0.2),
            'flags' => $faker->randomDigit(),
        ];
    }

    /**
     * @param FakerGenerator|Discord|Internet $faker
     * @param bool $includeBotFlag
     * @return array
     * @throws Exception
     */
    protected function generateUser($faker, bool $includeBotFlag = false)
    {
        $return = [
            'id' => $faker->userId(),
            'username' => $faker->userName(),
            'avatar' => $faker->optional(0.9)->randomElement([$faker->iconHash(), $faker->iconHash(true)]),
            'discriminator' => $faker->discriminator(),
            'public_flags' => $faker->randomDigit(),
        ];
        if ($includeBotFlag) {
            $return['bot'] = $faker->boolean();
        }

        return $return;
    }

    /**
     * @param FakerGenerator|Discord|Internet|MiscProvider $faker
     * @return array
     * @throws Exception
     */
    protected function generateGuildMember($faker)
    {
        $premiumSince = $faker->optional()->dateTimeInInterval('-2 years', 'now');
        if (!is_null($premiumSince)) {
            $premiumSince = $premiumSince->format(DiscordDatetimeInterface::FORMAT);
        }
        $pending = $faker->boolean();
        $roles = [];
        if (!empty($faker->optional(0.75)->passthrough(3))) {
            foreach ($faker->rangeBetween(3, 0) as $i) {
                $roles[] = $faker->roleId();
            }
        }
        return [
            'roles' => $roles,
            'nick' => $faker->optional()->userName(),
            'premium_since' => $premiumSince,
            'joined_at' => $faker->dateTimeInInterval('-2 years', 'now')->format(DiscordDatetimeInterface::FORMAT),
            'is_pending' => $pending,
            'pending' => $pending,
            'user' => $this->generateUser($faker),
            'mute' => $faker->boolean(),
            'deaf' => $faker->boolean(),
        ];
    }

    /**
     * @param FakerGenerator|Discord|Internet|MiscProvider $faker
     * @param string|null $guildId
     * @param bool $excludeTags
     * @return array
     */
    protected function generateRole($faker, string $guildId = null, bool $excludeTags = false)
    {
        $tags = [];
        if (!$guildId && !$excludeTags && $faker->boolean()) {
            if ($faker->boolean()) {
                $tags['bot_id'] = $faker->userId();
            }
            if ($faker->boolean()) {
                $tags['integration_id'] = $faker->snowflake();
            }
            if (empty($tags) && $faker->boolean()) {
                $tags['premium_subscriber'] = true;
            }
        }
        return [
            'id' => $guildId ?? $faker->roleId(),
            'name' => $guildId ? '@everyone' : $faker->words(3, true),
            'permissions' => (string)$faker->permissionInteger(),
            'position' => $guildId ? 0 : $faker->randomDigit(),
            'color' => $guildId ? 0 : $faker->randomElement([0, $faker->embedColor()]),
            'hoist' => $guildId ? false : $faker->boolean(),
            'managed' => array_key_exists('bot_id', $tags),
            'mentionable' => $faker->boolean(),
            'tags' => $tags
        ];
    }
}
