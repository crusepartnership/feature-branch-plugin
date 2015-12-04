<?php
namespace Lucidity\Composer;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Package\Link;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use Composer\Semver\Constraint\Constraint;

class FeatureBranchPlugin implements PluginInterface, EventSubscriberInterface
{
    /**
     * @var Composer
     */
    protected $composer;

    /**
     * @var IOInterface
     */
    protected $io;

    /**
     * @var array
     */
    protected $featureBranchRepositories;

    /**
     * Apply plugin modifications to composer
     *
     * @param Composer    $composer
     * @param IOInterface $io
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
        $extra = $composer->getPackage()->getExtra();
        $this->featureBranchRepositories = isset($extra['feature-branch-repositories']) ? $extra['feature-branch-repositories'] : [];
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     * * The method name to call (priority defaults to 0)
     * * An array composed of the method name to call and the priority
     * * An array of arrays composed of the method names to call and respective
     *   priorities, or 0 if unset
     *
     * For instance:
     *
     * * array('eventName' => 'methodName')
     * * array('eventName' => array('methodName', $priority))
     * * array('eventName' => array(array('methodName1', $priority), array('methodName2'))
     **
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents()
    {
        return [
            ScriptEvents::PRE_INSTALL_CMD => ['resolveFeatureBranch', 0],
            ScriptEvents::PRE_UPDATE_CMD => ['resolveFeatureBranch', 0]
        ];
    }

    public function resolveFeatureBranch(Event $event)
    {
        if (empty($this->featureBranchRepositories)) {
            $this->io->write('No feature branches configured, continuing!');
            return;
        }
        $package = $this->composer->getPackage();
        if ($package->isDev()) {
            $featureBranchConstraint = new Constraint('=', $package->getVersion());
            $featureBranchConstraint->setPrettyString($package->getVersion());
            $requires = $package->getRequires();
            $this->io->write(sprintf("<info>Checking for feature branch '%s'</info>",
                $featureBranchConstraint->getPrettyString()));
            foreach ($requires as $key => $require) {
                if ($this->hasFeatureBranch($require, $featureBranchConstraint)) {
                    $requires[$key] = new Link(
                        $require->getSource(),
                        $require->getTarget(),
                        $featureBranchConstraint,
                        'requires',
                        $featureBranchConstraint->getPrettyString()
                    );
                }
            }
            $package->setRequires($requires);
        }
    }

    private function hasFeatureBranch(Link $require, Constraint $requiredConstraint)
    {
        if (in_array($require->getTarget(), $this->featureBranchRepositories)) {
            $this->io->write(sprintf('<info>%s</info>', $require->getTarget()), false);
            $package = $this->composer->getRepositoryManager()->findPackage($require->getTarget(), $requiredConstraint);
            if ($package) {
                $this->io->write(" - <info>switching to branch</info>");
                return true;
            }
            $this->io->write(" - <warning>branch not found</warning>");
        }
        return false;
    }
}
