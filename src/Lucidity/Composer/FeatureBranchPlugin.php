<?php
namespace Lucidity\Composer;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\InstallerEvent;
use Composer\Installer\InstallerEvents;
use Composer\IO\IOInterface;
use Composer\Package\LinkConstraint\LinkConstraintInterface;
use Composer\Package\LinkConstraint\VersionConstraint;
use Composer\Plugin\PluginInterface;

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
     * Apply plugin modifications to composer
     *
     * @param Composer    $composer
     * @param IOInterface $io
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
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
            InstallerEvents::PRE_DEPENDENCIES_SOLVING => ['resolveFeatureBranch', 0]
        ];
    }

    /**
     * @param InstallerEvent $event
     */
    public function resolveFeatureBranch(InstallerEvent $event)
    {
        $package = $event->getComposer()->getPackage();
        $featureBranch = $package->isDev() ? $package->getVersion() : null;
        if ($featureBranch !== null) {
            $request = $event->getRequest();
            $featureBranchConstraint = new VersionConstraint('=', $featureBranch);
            foreach ($this->featureJobs($event, $featureBranchConstraint) as $featureJob) {
                $request->fix($featureJob['packageName'], $featureBranchConstraint);
            }
        }
    }

    /**
     * @param InstallerEvent          $event
     * @param LinkConstraintInterface $requiredConstraint
     *
     * @return array
     */
    private function featureJobs(InstallerEvent $event, LinkConstraintInterface $requiredConstraint)
    {
        $pool = $event->getPool();
        $extra = $this->composer->getPackage()->getExtra();
        $featureBranchRepositories = isset($extra['feature-branch-repositories']) ? $extra['feature-branch-repositories'] : [];
        $request = $event->getRequest();
        $featureJobs = [];
        foreach ($request->getJobs() as $job) {
            $packageMatches = isset($job['packageName']) && in_array($job['packageName'], $featureBranchRepositories);
            $hasVersion = $packageMatches && count($pool->whatProvides($job['packageName'], $requiredConstraint));
            if ($hasVersion) {
                $featureJobs[] = $job;
            }
        }
        return $featureJobs;
    }
}
