/**
 * $Id: ConsoleUtils.cs 345 2007-11-03 18:17:08Z spocke $
 *
 * @author Moxiecode
 * @copyright Copyright © 2004-2006, Moxiecode Systems AB, All rights reserved.
 */

using System;
using System.IO;
using System.Collections;

namespace Moxiecode.JSTrim.Utils {
 	/**
 	 * This class contains console utils methods. To help the development of console based apps.
 	 */
	public class ConsoleUtils {
 		// Private fields
 		private string[] args;
 		private int logLevel = INFO;

 		/**
 		 * <summary>Debug level for console logging.</summary>
 		 */
 		public const int DEBUG = 10;

 		/**
 		 * <summary>Info level for console logging.</summary>
 		 */
		public const int INFO = 20;

 		/**
 		 * <summary>Warning level for console logging.</summary>
 		 */
		public const int WARNING = 30;

 		/**
 		 * <summary>Error level for console logging.</summary>
 		 */
		public const int ERROR = 40;

 		/**
 		 * <summary>Fatal level for console logging.</summary>
 		 */
		public const int FATAL = 50;

		/**
		 * 
		 */
		public ConsoleUtils(string[] args) {
			this.args = args;
		}

		/**
		 * <summary>Current log level to be used for console.</summary>
		 */
		public int LogLevel {
			get {
				return logLevel;
			}
			set {
				logLevel = value;
			}
		}

		/**
		 * <summary>Application arguments array.</summary>
		 */
		public string[] Arguments {
			get {
				return args;
			}
			set {
				args = value;
			}
		}

		/**
		 * <summary>
		 *  Returns a parameter value by a parameter name or short name.
		 * </summary>
		 * <param name="name">Name of parameter to get</param>
		 * <param name="short_name">Short name of parameter to get</param>
		 * <param name="default_value">Defulat value, if parameter wasn't found.</param>
		 * <returns>parameter value or null if it wasn't found</returns>
		 */
		public string GetParameterByName(string name, String short_name, string default_value) {
			for (int i=0; i<this.args.Length; i++) {
				if (this.args[i] == name || this.args[i] == short_name) {
					if ((i+1) < this.args.Length)
						return this.args[i+1];

					return default_value;
				}
			}

			return default_value;
		}

		/**
		 * <summary>
		 *  Returns a int parameter value by a parameter name or short name.
		 * </summary>
		 * <param name="name">name of parameter to get</param>
		 * <param name="short_name">short name of parameter to get</param>
		 * <param name="default_value">Defulat value, if parameter wasn't found.</param>
		 * <returns>parameter int value or -1 if it wasn't found</returns>
		 */
		public int GetParameterByNameInt(string name, string short_name, int default_value) {
			string value = null;

		 	if ((value = this.GetParameterByName(name, short_name, null)) != null)
		 		return (int) Int32.Parse(value);

		 	return default_value;
		}
 
		/**
		 * <summary>
		 *  Checks the existance of a parameter by a parameter name or short name.
		 * </summary>
		 * <param name="name">name of parameter to get</param>
		 * <param name="short_name">short name of parameter to get</param>
		 * <returns>true - it exists, false - it doesn't exist</returns>
		 */
		public bool CheckParameterByName(string name, string short_name) {
			for (int i=0; i<this.args.Length; i++) {
				if (this.args[i] == name || this.args[i] == short_name)
					return true;
			}

			return false;
		}

		/**
		 * <summary>
		 *  Removes a parameter by name or short name.
		 * </summary>
		 * <param name="index">Index of argument to remove.</param>
		 * <param name="length">Number of arguments to remove.</param>
		 * <returns>true - it was removed, false - it doesn't exist</returns>
		 */
		public bool RemoveArguments(int index, int length) {
		 	ArrayList newArgs = new ArrayList();
		 	bool deleted = false;

			for (int i=0; i<args.Length; i++) {
		 		if (i >= index && i <= (i + length)) {
					deleted = true;
		 			continue;
		 		}

		 		newArgs.Add(args[i]);
		 	}

		 	this.args = (String[]) newArgs.ToArray(typeof(String));

		 	return deleted;
		}

		/**
		 * <summary>
		 *  Removes a parameter by name or short name.
		 * </summary>
		 * <param name="name">name of parameter to remove.</param>
		 * <param name="short_name">short name of parameter to remove.</param>
		 * <returns>true - it was removed, false - it doesn't exist</returns>
		 */
		public bool RemoveParameter(string name, string short_name) {
		 	return this.RemoveParameter(name, short_name, false);
		}

		/**
		 * <summary>
		 *  Removes a parameter by name or short name.
		 * </summary>
		 * <param name="name">name of parameter to remove.</param>
		 * <param name="short_name">short name of parameter to remove.</param>
		 * <param name="single">True/False state if the parameter is a name/value or just name.</param>
		 * <returns>true - it was removed, false - it doesn't exist</returns>
		 */
		public bool RemoveParameter(string name, string short_name, bool single) {
		 	ArrayList newArgs = new ArrayList();
		 	bool deleted = false;

			for (int i=0; i<args.Length; i++) {
		 		if (args[i] == name || args[i] == short_name) {
					deleted = true;
		 			
		 			// Skip next to
		 			if (!single && i < args.Length)
		 				i++;

		 			continue;
		 		}

		 		newArgs.Add(args[i]);
		 	}

		 	this.args = (String[]) newArgs.ToArray(typeof(String));

		 	return deleted;
		}

		/// <summary>
		///  Returns a list or files based on path. This method
		///  will handle single file lists and wildcard patterns.
		/// </summary>
		/// <param name="path">Directory/File pattern or single file.</param>
		/// <param name="recursive">List files recursive on/off.</param>
		/// <returns>List of files or null if it wasn't found.</returns>
		public string[] GetFiles(string path, bool recursive) {
			return this.GetFiles(path, recursive, "*.*");
		}

		/// <summary>
		///  Returns a list or files based on path. This method
		///  will handle single file lists and wildcard patterns.
		/// </summary>
		/// <param name="path">Directory/File pattern or single file.</param>
		/// <param name="recursive">List files recursive on/off.</param>
		/// <param name="pattern">Default pattern to use if no pattern was defined.</param>
		/// <returns>List of files or null if it wasn't found.</returns>
		public string[] GetFiles(string path, bool recursive, string pattern) {
			string[] files;

			// Force UNIX style
			path = path.Replace('\\', '/');

			// Path has pattern
			if (path.IndexOf('*') != -1) {
				if (path.LastIndexOf('/') != -1) {
					pattern = path.Substring(path.LastIndexOf('/') + 1);
					path = path.Substring(0, path.LastIndexOf('/'));
				} else {
					pattern = path;
					path = ".";
				}
			}

			// Handle listing
			if (Directory.Exists(path)) {
				files = this.ListFiles(path, recursive, pattern);

				return files;
			} else if (File.Exists(path)) {
				files = new string[1];

				files[0] = path;

				return files;
			}

			return null;
		}

		/**
		 * <summary>Log a message to console.</summary>
		 * <param name="level">Log level to log message as.</param>
		 * <param name="msg">Message to display in console.</param>
		 */
		public void Log(int level, string msg) {
			if (level >= this.logLevel)
				Console.WriteLine(msg);
		}

		/**
		 * <summary>Log a debug message to console.</summary>
		 * <param name="msg">Message to display in console.</param>
		 */
		public void LogDebug(string msg) {
			this.Log(DEBUG, msg);
		}

		/**
		 * <summary>Log a info message to console.</summary>
		 * <param name="msg">Message to display in console.</param>
		 */
		public void LogInfo(string msg) {
			this.Log(INFO, msg);
		}

		/**
		 * <summary>Log a warning message to console.</summary>
		 * <param name="msg">Message to display in console.</param>
		 */
		public void LogWarning(string msg) {
			this.Log(WARNING, msg);
		}

		/**
		 * <summary>Log a error message to console.</summary>
		 * <param name="msg">Message to display in console.</param>
		 */
		public void LogError(string msg) {
			this.Log(ERROR, msg);
		}

		/**
		 * <summary>Log a message to console.</summary>
		 * <param name="msg">Message to display in console.</param>
		 */
		public void LogFatal(string msg) {
			this.Log(FATAL, msg);
		}

		private string[] ListFiles(string path, bool recurisve, string pattern) {
			ArrayList fileList = new ArrayList();
			string[] files, dirs;

			files = Directory.GetFiles(path, pattern);

			foreach (string file in files)
				fileList.Add(file.Replace('\\', '/'));

			if (recurisve) {
				dirs = Directory.GetDirectories(path);

				foreach (string dir in dirs)
					fileList.AddRange(this.ListFiles(dir, recurisve, pattern));
			}

			return (string[]) fileList.ToArray(typeof(string));
		}
	}
}
