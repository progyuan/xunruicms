<?php namespace CodeIgniter\Debug\Toolbar\Collectors;

/**
 * Debug工具栏模板类
 */

use CodeIgniter\Config\Services;
use CodeIgniter\View\RendererInterface;

/**
 * Views collector
 */
class Views extends BaseCollector
{

    /**
     * Whether this collector has data that can
     * be displayed in the Timeline.
     *
     * @var bool
     */
    protected $hasTimeline = false;

    /**
     * Whether this collector needs to display
     * content in a tab or not.
     *
     * @var bool
     */
    protected $hasTabContent = true;

    /**
     * Whether this collector needs to display
     * a label or not.
     *
     * @var bool
     */
    protected $hasLabel = true;

    /**
     * Whether this collector has data that
     * should be shown in the Vars tab.
     *
     * @var bool
     */
    protected $hasVarData = true;

    /**
     * The 'title' of this Collector.
     * Used to name things in the toolbar HTML.
     *
     * @var string
     */
    protected $title = 'Views';

    /**
     * Instance of the Renderer service
     * @var RendererInterface
     */
    protected $viewer;

    /**
     * Views counter
     *
     * @var array
     */
    protected $views = [];

    //--------------------------------------------------------------------

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->viewer = \Phpcmf\Service::V();
    }

    //--------------------------------------------------------------------

    /**
     * Child classes should implement this to return the timeline data
     * formatted for correct usage.
     *
     * @return mixed
     */
    protected function formatTimelineData(): array
    {
        $data = [];

        $rows = $this->viewer->getPerformanceData();

        foreach ($rows as $name => $info)
        {
            $data[] = [
                'name'		 => 'View: ' . $info['view'],
                'component'	 => 'Views',
                'start'		 => $info['start'],
                'duration'	 => $info['end'] - $info['start']
            ];
        }

        return $data;
    }

    //--------------------------------------------------------------------

    /**
     * Gets a collection of data that should be shown in the 'Vars' tab.
     * The format is an array of sections, each with their own array
     * of key/value pairs:
     *
     *  $data = [
     *      'section 1' => [
     *          'foo' => 'bar,
     *          'bar' => 'baz'
     *      ],
     *      'section 2' => [
     *          'foo' => 'bar,
     *          'bar' => 'baz'
     *      ],
     *  ];
     *
     * @return null
     */
    public function getVarData()
    {
        return [
            'View Data' => $this->viewer->get_data()
        ];
    }

    //--------------------------------------------------------------------

    /**
     * Returns a count of all views.
     *
     * @return int
     */
    public function getBadgeValue()
    {
        return dr_count($this->viewer->getPerformanceData());
    }

    public function display(): array
    {
       // $parser = \Config\Services::parser(BASEPATH . 'Debug/Toolbar/Views/', null,false);

        $data = [
            'views' => []
        ];

        foreach ($this->viewer->getPerformanceData() as $row)
        {
            $key = $row['view'];

            if (! array_key_exists($key, $data['views']))
            {
                $data['views'][$key] = [
                    'view' => $key,
                    'duration' => number_format(($row['end']-$row['start']) * 1000, 2),
                    'count' => 1,
                ];

                continue;
            }

            $data['views'][$key]['duration'] += number_format(($row['end']-$row['start']) * 1000, 2);
            $data['views'][$key]['count']++;
        }

        //$output = $parser->setData($data)->render('_views.tpl');

        return $data;
    }

    /**
     * Display the icon.
     *
     * Icon from https://icons8.com - 1em package
     *
     * @return string
     */
    public function icon(): string
    {
        return <<<EOD
data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsMAAA7DAcdvqGQAAADeSURBVEhL7ZSxDcIwEEWNYA0YgGmgyAaJLTcUaaBzQQEVjMEabBQxAdw53zTHiThEovGTfnE/9rsoRUxhKLOmaa6Uh7X2+UvguLCzVxN1XW9x4EYHzik033Hp3X0LO+DaQG8MDQcuq6qao4qkHuMgQggLvkPLjqh00ZgFDBacMJYFkuwFlH1mshdkZ5JPJERA9JpI6xNCBESvibQ+IURA9JpI6xNCBESvibQ+IURA9DTsuHTOrVFFxixgB/eUFlU8uKJ0eDBFOu/9EvoeKnlJS2/08Tc8NOwQ8sIfMeYFjqKDjdU2sp4AAAAASUVORK5CYII=
EOD;

    }
}
