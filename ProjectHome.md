# Smart Security Grep #

SSGrep is a simple script useful to grep source code during code review
or security assessment.
It's nothing more than a smart security grep!

## Requirements ##

SSGrep is completely written in PHP. To run it, you just have to install
the PHP interpreter with the CLI option.
This tool has been developed and tested on a Gentoo Linux box.

## Usage ##
```
Usage: ssgrep [options] <input resources>
		<input resource>. Required. Files, Directories, ecc.
		--kb=<knowledge base>. Optional. Available modes are:
			j/java - Search for dangerous Java/JSP Methods
			s/sensitive - Search for sensitive data 
			l/lamer - Search for lamer developers comments
			m/misc - Search miscellaneous strings
			a/all - Search all
		--l=<language>. Optional. Available modes are:
			eng - Look for english keywords
			ita - Look for italian keywords
			all - Consider all languages
		--o=<output file>. Optional. Available output files are:
			.html- Show results in a comfortable HTML file
		--v. Optional. Show informations during the grep process
		--h. Optional. Display this help

Example: ./ssgrep --o=result.html /output
```