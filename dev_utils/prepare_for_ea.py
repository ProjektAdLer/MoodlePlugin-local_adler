""""
This script prepares the adler moodle plugin for Enterprise Architect diagram generation.
EA does not support typedefs for function/method parameters. This script will remove them.
Also not all files are relevant for the diagram generation.
This script will only process the relevant files as defined in the relevant_paths variable.
It can contain folder and file paths.
Processed files will be copied to the target_path. source_path will not be modified.
""""

import re
import os


# config
source_path = "/mnt/c/Users/heckmarm/Desktop/ea-moodle-plugin/adler_source"
target_path = "/mnt/c/Users/heckmarm/Desktop/ea-moodle-plugin/adler_target_target"
relevant_paths = [
    "backup",
    "classes"
]
# end config


def remove_typedef_and_copy(filepath_source, filepath_target):
#     filepath = '/mnt/c/Users/heckmarm/Desktop/ea-moodle-plugin/adler_source/classes/dsl_score.php'
    # open file
    with open(filepath_source, 'r') as f:
        # read file
        data = []
        for line in f:
            data.append(line)

    # remove typedefs
    regex_function_def = "function.*\(.*\)"
    regex_typedef = "(?<=[\(,])[a-zA-Z ]*(?=\$)"
    for line in data:
        if re.search(regex_function_def, line):
            # remove matches with regex_typedef
            line = re.sub(regex_typedef, '', line)

    # ensure folder exists
    os.makedirs(os.path.dirname(filepath_target), exist_ok=True)

    # open the input file in write mode
    with open(filepath_target, 'w') as f:
        # write the data to the file
        for line in data:
            f.write(line)



# remove / at end of path
if source_path[-1] == "/":
    source_path = source_path[:-1]
if target_path[-1] == "/":
    target_path = target_path[:-1]

for relevant_path in relevant_paths:
    for path, subdirs, files in os.walk(os.path.join(source_path, relevant_path)):
        for name in files:
            source_file = os.path.join(path, name)
            target_file = source_file.replace(source_path, target_path)

            remove_typedef_and_copy(source_file, target_file)

#             print("source_file: " + source_file)
#             print("target_file: " + target_file)
