/**
 * $Id: JSTrim.cs 345 2007-11-03 18:17:08Z spocke $
 *
 * @author Moxiecode
 * @copyright Copyright © 2004-2006, Moxiecode Systems AB, All rights reserved.
 */

using System;
using System.IO;
using System.Xml;
using System.Text.RegularExpressions;
using System.Collections;
using Dean.Edwards;
using Moxiecode.JSTrim.Utils;

namespace Moxiecode.JSTrim {
	/// <summary>
	///  Packing mode.
	/// </summary>
	public enum PackMode {
		/// <summary>
		///  Documentation mode. This will remove all JSDoc comments from a file but
		///  leave the whitespace intact.
		/// </summary>
		Documentation,

		/// <summary>
		///  Low compression mode, this will simply remove all whitespace.
		/// </summary>
		Low,

		/// <summary>
		///  Packing mode, this will use Dean Edwards algoritm to Pack the Javascript.
		/// </summary>
		High
	}

	/// <summary>
	///  Helper class for JavaScript compression.
	/// </summary>
	public class JSTrim {
		// Private data fields
		private ECMAScriptPacker packer;
		private bool quiet, force;
		private PackMode mode;

		/// <summary>
		///  Main constructor for the JSTrim class.
		/// </summary>
		public JSTrim() {
			this.packer = new ECMAScriptPacker();
			this.Mode = PackMode.Low;
			this.Force = false;
			this.Quiet = false;
		}

		/// <summary>
		///  Packer class reference.
		/// </summary>
		public ECMAScriptPacker Packer {
			get {
				return packer;
			}
		}

		/// <summary>
		///  Packer mode, high, low or docs.
		/// </summary>
		public PackMode Mode {
			get {
				return mode;
			}

			set {
				switch (value) {
					case PackMode.High:
						this.packer.Encoding = ECMAScriptPacker.PackerEncoding.Normal;
						break;

					case PackMode.Low:
						this.packer.Encoding = ECMAScriptPacker.PackerEncoding.None;
						break;
				}

				mode = value;
			}
		}

		/// <summary>
		///  Quitet mode, if set to true all console messages will be ommited.
		/// </summary>
		public bool Quiet {
			get {
				return quiet;
			}

			set {
				quiet = value;
			}
		}

		/// <summary>
		///  Force compression. This will skip any last modification date checking of
		///  files and force a regeneration of all files.
		/// </summary>
		public bool Force {
			get {
				return force;
			}

			set {
				force = value;
			}
		}

		/// <summary>
		///  Process a XML JSTrim configuration file.
		/// </summary>
		/// <param name="path">JSTrim XML configuration file.</param>
		public void ProcessConfig(string path) {
			FileInfo configFileInfo = new FileInfo(path);
			XmlDocument xmlDoc = new XmlDocument();
			XmlNodeList nodes, addElms;
			XmlElement actionElm, addElement, rootElm;
			string content, dir, src, dest, defines;
			bool oldForce, oldSpecialChars, oldFastDecode;
			PackMode oldMode = this.Mode;

			oldForce = this.force;
			oldSpecialChars = this.packer.SpecialChars;
			oldFastDecode = this.packer.FastDecode;

			if (configFileInfo.Exists) {
				if (!this.quiet)
					Console.WriteLine("Processing JSDoc config file: " + path);

				xmlDoc.Load(configFileInfo.FullName);

				rootElm = xmlDoc.DocumentElement;
				switch (rootElm.GetAttribute("mode").ToLower()) {
					case "docs":
						this.Mode = PackMode.Documentation;
						break;

					case "high":
						this.Mode = PackMode.High;
						break;

					case "low":
						this.Mode = PackMode.Low;
						break;
				}

				if (rootElm.GetAttribute("force").ToLower() == "true")
					this.force = true;

				// Loop all actions
				nodes = xmlDoc.SelectNodes("//*");
				for (int i=0; i<nodes.Count; i++) {
					actionElm = (XmlElement) nodes.Item(i);

					switch (actionElm.Name) {
						case "del":
							File.Delete(actionElm.GetAttribute("src"));
							break;

						case "trim":
							content = "";

							oldMode = this.Mode;
							oldForce = this.force;
							oldSpecialChars = this.packer.SpecialChars;
							oldFastDecode = this.packer.FastDecode;

							switch (actionElm.GetAttribute("mode").ToLower()) {
								case "docs":
									this.Mode = PackMode.Documentation;
									break;

								case "high":
									this.Mode = PackMode.High;
									break;

								case "low":
									this.Mode = PackMode.Low;
									break;
							}

							if (actionElm.GetAttribute("force").ToLower() == "true")
								this.force = true;

							dir = actionElm.GetAttribute("dir");
							src = actionElm.GetAttribute("src");
							dest = actionElm.GetAttribute("dest");
							defines = actionElm.GetAttribute("defines");

							if (actionElm.HasChildNodes) {
								if (!this.Quiet)
									Console.WriteLine("Appending files to: " + dest);

								addElms = actionElm.SelectNodes("add");

								foreach (XmlNode addNode in addElms) {
									addElement = (XmlElement) addNode;
									src = addElement.GetAttribute("src");
									content += "\r\n/* file:" + src + " */\r\n\r\n";
									content += ReadFile(src);

									if (!this.Quiet)
										Console.WriteLine("  Appended: " + src);
								}

								content = this.Pack(content, defines.Split(new char[]{','}));
								WriteFile(dest, content);

								this.Mode = oldMode;
								this.force = oldForce;
								this.packer.SpecialChars = oldSpecialChars;
								this.packer.FastDecode = oldFastDecode;

								break;
							}

							if (dir != "")
								PackDir(dir, dest, defines.Split(new char[]{','}));
							else
								PackFile(src, dest, defines.Split(new char[]{','}));

							this.Mode = oldMode;
							this.force = oldForce;
							this.packer.SpecialChars = oldSpecialChars;
							this.packer.FastDecode = oldFastDecode;

							break;
					}
				}
			}
		}

		/// <summary>
		///  Packs the specified file and checks the last modfication date of src.
		/// </summary>
		/// <param name="src">Source file to compress.</param>
		/// <param name="dest">Destination file to compress to.</param>
		/// <param name="defines">Defines.</param>
		/// <returns>true - success, false - failure</returns>
		public bool PackFile(string src, string dest, string[] defines) {
			FileInfo srcInfo = new FileInfo(src);
			FileInfo destInfo = new FileInfo(dest);
			DateTime now = DateTime.Now;

			if (srcInfo.Exists && (this.Force || !destInfo.Exists || srcInfo.LastWriteTime != destInfo.LastWriteTime)) {
				WriteFile(dest, this.Pack(ReadFile(src), defines));

				if (!this.quiet)
					Console.WriteLine("Trimming[" + this.GetMode(this.mode) + "] " + src + " to " + dest);

				srcInfo.LastWriteTime = now;
				destInfo.LastWriteTime = now;

				return true;
			} else if (!this.quiet)
				Console.WriteLine("Skipped trimming of " + src + " to " + dest);

			return false;
		}

		/// <summary>
		///  Packs the specified directory into a file.
		/// </summary>
		/// <param name="dir">Directory containing .js files to compress.</param>
		/// <param name="dest">Desctination file to pack to</param>
		public void PackDir(string dir, string dest, string[] defines) {
			string[] files = Directory.GetFiles(dir, "*.js");
			FileInfo destInfo = new FileInfo(dest);
			string content = "";
			bool trim = false;

			// Load all files
			foreach (string file in files) {
				content += ReadFile(file);

				if (new FileInfo(file).LastWriteTime != destInfo.LastWriteTime)
					trim = true;
			}

			// Needs to be trimmed
			if (trim) {
				WriteFile(dest, this.Pack(content, defines));

				if (!this.quiet)
					Console.WriteLine("Trimming dir " + dir + " to " + dest);
			}
		}

		/// <summary>
		///  Packs the specified contents based on the settings of this class.
		/// </summary>
		/// <param name="content">JS contents to pack.</param>
		/// <returns>Packed Javascript string.</returns>
		public string Pack(string content, string[] defines) {
			int pos = -1, startPos;
			Regex statementRe = new Regex(@"\/\/ #([a-z]+)\s?([!a-z0-9_]*)");
			Stack depth = new Stack();

			while ((pos = content.IndexOf("// #", pos + 1)) != -1) {
				Match match = statementRe.Match(content, pos);
				string statement = match.Groups[1].Value;
				string arg = match.Groups[2].Value;

				switch (statement) {
					case "if":
						bool found = false;
						bool not = false;

						if (arg.IndexOf('!') == 0) {
							not = true;
							arg = arg.Substring(1);
						}

						foreach (string define in defines) {
							if (define == arg) {
								found = true;
								break;
							}
						}

						if (not) {
							if (!found)
								depth.Push(-1);
							else
								depth.Push(pos);
						} else {
							if (found)
								depth.Push(-1);
							else
								depth.Push(pos);
						}

						break;

					case "endif":
						pos = content.IndexOf('\n', pos) + 1;

						startPos = (int) depth.Pop();
						if (startPos != -1) {
							content = content.Remove(startPos, pos - startPos);
							pos = startPos;
							/*Console.WriteLine("---");
							Console.WriteLine(content.Substring(startPos, pos - startPos));
							Console.WriteLine("---");*/
						}
						break;
				}
				
				//Console.WriteLine(statement + "," + arg);
			}

			if (mode == PackMode.Documentation)
				return Regex.Replace(content, @"/\*\*(.*?)\*\/\s*", "", RegexOptions.Singleline);

			return packer.Pack(content);
		}

		/// <summary>
		///  Reads all file contents.
		/// </summary>
		/// <param name="path">File to read.</param>
		/// <returns>String containing file data.</returns>
		public string ReadFile(string path) {
			string content;

			StreamReader sr = new StreamReader(path);
			content = sr.ReadToEnd();
			sr.Close();

			return content;
		}

		/// <summary>
		///  Writes contents to a file.
		/// </summary>
		/// <param name="path">File to write to.</param>
		/// <param name="content">Content to write.</param>
		public void WriteFile(string path, string content) {
			FileInfo pathInfo = new FileInfo(path);

			if (pathInfo.Exists)
				pathInfo.Delete();

			StreamWriter sw = File.CreateText(path);
			sw.Write(content);
			sw.Close();
		}

		/// <summary>
		///  Returns a string representation of the packmode enum.
		/// </summary>
		/// <param name="mode">Pack mode enum to returns as string.</param>
		/// <returns>String representation of the packmode enum</returns>
		private string GetMode(PackMode mode) {
			switch (mode) {
				case PackMode.Documentation:
					return "docs";

				case PackMode.High:
					return "high";

				case PackMode.Low:
					return "low";
			}

			return "unknown";
		}
	}
}
