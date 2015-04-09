# Introduction #
A web app for the coordination of "WWW" Interdisciplinary Summer Workshops (the name comes from the Polish acronym: Wakacyjne Warsztaty Wielodyscyplinarne). From accepting proposals from lecturers, to qualification task writing, solving and grading, to various forms for information needed from participants to run our yearly event.

# Installation #
  * Check you have PHP>=5.2, PostgreSQL>=9 (modifying the driver to support another DBMS should be easy, but I fear many SELECTs may assume postgres-specific syntax).
  * Copy all the files (~15MiB) from the repository (git clone https://code.google.com/p/www-app/).
  * Create a db and run _install.sql_ (change the _`w1_`_ to another non-empty prefix with _`sed -i s/w1_/prefix_/g install.sql`_, if you want)
  * Fill _config.php.template_ and save as _config.php_ (with minimal rights).
  * Open in browser and log in as _root_ (you can then add the _admin_ role to a new account or rename _root_).
  * Check options in menu->settings (in particular, create a new edition).
  * Some functionalities may require tweaking or abandoning, like latex and file uploading (CGI modules: _mimetex_ and _uber-upload_).

# Life cycle #
At any moment new users can register accounts, admins can give them new roles. Who can do what is decided mainly by the table\_role\_permissions.
An edition is the whole event, e.g. the 7th Interdisciplinary Summer Workshops at Olsztyn.
An admin can create a new edition or switch the current edition to an existing one, making only this edition's elements visible; all operations will affect only that edition (other editions are hidden in the database, sometimes old data is copied by default, user profiles are kept mostly intact, but their roles change per edition).

Newly registered users (after confirming their e-mail), see a list of things to do on the homepage, they can follow two paths - either propose a workshop block (a lecture) and get qualified by having a proposal accepted, or candidate as a regular participant and get qualified by solving qualification tasks. By clicking on "apply as participant" or "apply as lecturer" they acquire a new role and can follow the todo list.

Lecturers (=staff) submit descriptions of what they would like to do (after the deadline a warning shows up). Admins accept/reject all workshops and (later) qualify or not every lecturer (until getting qualified, lecturers shouldn't be able to see their status). After getting accepted, they write a description for participants (outside the app) and qualification tasks. Finally they check and grade solutions (which by default arrive by email).

Participants sign up for at least 4 workshop blocks, write a 'motivation letter', solve qualification tasks. Admins then look at point summaries, letters, etc. and decide to qualify or not. If some workshop block turns out to not to be interesting enough (counting or not lecturers, who also may sign up for workshops), admins may decide to un-qualify a lecturer and his workshops (though I'm not sure it ever happened).

Everyone has to fill in his profile and some additional data, when qualified. All this is summarized for admins in various tables.

# Documentation #
[Doxygen](http://www-app.googlecode.com/git/doxygen/html/globals.html) (functions, globals and classes list).

# Development #
I will be trying to add more documentation with time. For now I hope the concepts of $DB, $PAGE, parseTable, enums, user rights, etc. should be natural enough to be easily grasped by looking at examples. Feel free to submit issues if you would like us to change something and of course any submissions in code are welcome. I keep a todo-list in [todo.txt](http://www-app.googlecode.com/git/todo.txt).

Internationalization is implemented with standard gettext, so to add a language it should be enough to edit the appropriate .po file in _locale/_ with _poEdit_, for example, and change the locale in _common.php_. Emails (_notify.php_) and _tutoring.php_ (a functionality you probably won't use) have not been localized yet.

When looking at a user's profile there's an option to impersonate him - this will execute everything exactly as if one were logged in as him - very handy for testing.

Whenever you change some strings to be translated or add an icon, you should run _php updateTranslations.php_ or _mapIcons.py_, respectively. See [README.txt](http://www-app.googlecode.com/git/README.txt).
