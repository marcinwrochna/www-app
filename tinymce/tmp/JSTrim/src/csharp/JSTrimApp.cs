/**
 * $Id: JSTrimApp.cs 345 2007-11-03 18:17:08Z spocke $
 *
 * @author Moxiecode
 * @copyright Copyright © 2004-2006, Moxiecode Systems AB, All rights reserved.
 */

using System;
using System.IO;
using System.Xml;
using System.Text.RegularExpressions;
using System.Security.Permissions;
using Dean.Edwards;
using Moxiecode.JSTrim.Utils;

namespace Moxiecode.JSTrim {
	/// <summary>
	///  Contains logic for the console application.
	/// </summary>
	public class JSTrimApp {
		/// <summary>Main method for console app.</summary>
		/// <param name="args">Console app arguments.</param>
		public static void Main(string[] args) {
			FileInfo configFile;
			JSTrim jsTrim = new JSTrim();
			ConsoleUtils consoleUtils = new ConsoleUtils(args);
			bool recursive;
			string content, outputFile, defines;

			configFile = new FileInfo("JSTrim.config");

			// Print usage
			if (args.Length == 0 && !configFile.Exists || consoleUtils.CheckParameterByName("--help", "-h")) {
				PrintUsage();
				return;
			}

			jsTrim.Quiet = consoleUtils.CheckParameterByName("--quiet", "-q");
			jsTrim.Force = consoleUtils.CheckParameterByName("--force", "-f");
			jsTrim.Force = consoleUtils.CheckParameterByName("--force", "-f");
			recursive = consoleUtils.CheckParameterByName("--recursive", "-r");

			if (!jsTrim.Quiet && jsTrim.Force)
				Console.WriteLine("Update on all files forced.");

			// Process options
			switch (consoleUtils.GetParameterByName("--mode", "-m", "").ToLower()) {
				case "docs":
					jsTrim.Mode = PackMode.Documentation;
					break;

				case "high":
					jsTrim.Mode = PackMode.High;
					break;

				case "low":
					jsTrim.Mode = PackMode.Low;
					break;
			}

			// Process config
			jsTrim.ProcessConfig(consoleUtils.GetParameterByName("--config", "-c", "JSTrim.config"));
			defines = consoleUtils.GetParameterByName("--defines", "-d", "");

			// Remove arguments so that we only get a file list
			consoleUtils.RemoveParameter("--config", "-c");
			consoleUtils.RemoveParameter("--force", "-f", true);
			consoleUtils.RemoveParameter("--mode", "-m");
			consoleUtils.RemoveParameter("--quiet", "-q", true);
			consoleUtils.RemoveParameter("--help", "-h", true);
			consoleUtils.RemoveParameter("--recursive", "-r", true);
			consoleUtils.RemoveParameter("--defines", "-d");

			// Trim cmd line files
			if (consoleUtils.Arguments.Length >= 2) {
				outputFile = consoleUtils.Arguments[consoleUtils.Arguments.Length - 1];
				consoleUtils.RemoveArguments(consoleUtils.Arguments.Length - 1, 1);

				content = "";

				if (!jsTrim.Quiet)
					Console.WriteLine("Compressing files to: " + outputFile);

				foreach (string path in consoleUtils.Arguments) {
					string[] files = consoleUtils.GetFiles(path, recursive);

					foreach (string file in files) {
						if (!jsTrim.Quiet)
							Console.WriteLine("  Compressing file: " + file);

						content += jsTrim.ReadFile(file);
					}
				}

				jsTrim.WriteFile(outputFile, jsTrim.Pack(content, defines.Split(new char[]{','})));
			}
		}

		public static void PrintUsage() {
			Console.WriteLine("USAGE");
			Console.WriteLine("  jstrim <options> <src file> <dest file>");
			Console.WriteLine("");
			Console.WriteLine("OPTIONS");
			Console.WriteLine("  -c, --config <file> : Config file to use");
			Console.WriteLine("  -f, --force         : Force update of all files");
			Console.WriteLine("  -m, --mode <mode>   : Pack mode low, docs, high");
			Console.WriteLine("  -q, --quiet         : Quiet mode, no console output.");
			Console.WriteLine("  -r, --recursive     : Recursive handeling of directories.");
			Console.WriteLine("  -d, --defined       : Comma separated list of defines.");
			Console.WriteLine("  -h, --help          : Prints this help message.");
			Console.WriteLine("");
			Console.WriteLine("EXAMPLES");
			Console.WriteLine("  jstrim script.js compressed.js");
			Console.WriteLine("  jstrim *.js compressed.js");
			Console.WriteLine("  jstrim --config someconfig.config");
		}
	}
}
