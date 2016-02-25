# Nooku Scheduler

## What is Nooku Scheduler?

Nooku Scheduler is a re-usable component offering job sheduling and management for [Joomlatools Framework].

## Requirements

- Nooku Framework 2 or newer.
- PHP 5.2 or newer.
- MySQL 5.

## Installation

Nooku Scheduler can and should be installed by using [Composer](https://getcomposer.org/).

Go to the root directory of your Joomla installation in command line and execute this command:

```
composer require nooku/nooku-scheduler:1.*
```

The component will be installed in the `vendor` folder of the root directory of your Joomla site. The composer installer 
will make sure that the component is bootstrapped from that location and made available to other components extending it 
or just making use of it.

Out of the box, Nooku Schedular will attempt to store jobs data in a database table named `[component]_scheduler_jobs`. An SQL dump file may 
be found under the `/resources/install` directory for creating this database table. This database needs to be manually 
created for the component to function properly. At the moment we only support MySQL.

## Contributing

Nooku Scheduler is an open source, community-driven project. Contributions are welcome from everyone. 
We have [contributing guidelines](CONTRIBUTING.md) to help you get started.

## Contributors

See the list of [contributors](https://github.com/nooku/nooku-scheduler/contributors).

## License 

Nooku Scheduler is free and open-source software licensed under the [GPLv3 license](LICENSE.txt).

## Community

Keep track of development and community news.

* Follow [@joomlatoolsdev on Twitter](https://twitter.com/joomlatoolsdev)
* Join [joomlatools/dev on Gitter](http://gitter.im/joomlatools/dev)
* Read the [Joomlatools Developer Blog](https://www.joomlatools.com/developer/blog/)
* Subscribe to the [Joomlatools Developer Newsletter](https://www.joomlatools.com/developer/newsletter/)

[Joomlatools Framework]: http://www.joomlatools.com/developer/framework/
