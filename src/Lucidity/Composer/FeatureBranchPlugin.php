<?php
namespace Lucidity\Composer;

use Composer\Composer;
use Composer\DependencyResolver\Request;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\InstallerEvent;
use Composer\Installer\InstallerEvents;
use Composer\IO\IOInterface;
use Composer\Package\LinkConstraint\MultiConstraint;
use Composer\Package\LinkConstraint\VersionConstraint;
use Composer\Package\RootPackageInterface;
use Composer\Plugin\PluginInterface;

class FeatureBranchPlugin implements PluginInterface, EventSubscriberInterface
{
    protected $composer;

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
        $featureBranch = $this->featureBranchName($package);
        if ($featureBranch !== null) {
            $request = $event->getRequest();
            $featureBranchConstraint = new VersionConstraint('=', $featureBranch);
            foreach ($this->getFeatureBranchJobs($request) as $featureJob) {
                $constraint = new MultiConstraint([$featureBranchConstraint, $featureJob['constraint']], false);
                $request->fix($featureJob['packageName'], $constraint);
            }
        }
    }

    /**
     * @param RootPackageInterface $package
     *
     * @return string|null
     */
    private function featureBranchName(RootPackageInterface $package)
    {
        return $package->isDev() ? $package->getVersion() : null;
    }

    private function getFeatureBranchJobs(Request $request)
    {
        return array_filter($request->getJobs(), function ($job) {
            return strpos($job['packageName'], 'crusepartnership') !== false;
        });
    }
}
