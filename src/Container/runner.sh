#!/bin/bash
# takes input arguments:  userFolder compilerName fileName outputCommand
# timeoutSec - for specifying the timeout seconds for program to execute
# userFolder - userFolder for mounting the files
# compiler - for specifying the name of compiler
# file - for specifying the name of file
# outputCommand - for specifying the run command for executable(in case of compiled language) 
timeoutSec=$1
userFolder=$2
compiler=$3
file=$4
outputCommand=$5
# STDERR redirects to (during compilation) error.txt and STDOUT(after successful compilation and running) in output.txt
exec 1>/$userFolder/output.txt 
exec 2>/$userFolder/error.txt


if [[ ! -z "$outputCommand" ]]; then
    # compiles the file with the compiler
    if [[ $compiler == "javac" ]]; then
        $compiler -d / /$userFolder/tmp/$file
        outputCommand='java Main'
    else
        $compiler /$userFolder/tmp/$file
    fi
   
    # '$? stores the return value of last executed shell command
    # compilation returns 0 on success 
    if [[ $? -eq 0 ]]; then
        # starts the clock to calculate time
        START=`date +%s.%N`

        # if timeout then last return signal i.e. $? will be 137
        timeout -s SIGKILL $timeoutSec $outputCommand <$userFolder/tmp/input.txt
        if [ $? -eq 137 ]; then
            # give timeout error in error.txt
            echo Time Limit Exceeded >$userFolder/error.txt
        fi

    fi
else
    # starts the clock to calculate time
    START=`date +%s.%N`

    # interpretes the file with the interpreter
    timeout -s SIGKILL $timeoutSec $compiler /$userFolder/tmp/$file <$userFolder/tmp/input.txt
    if [[ $? -eq 137 ]]; then
        echo Time Limit Exceeded >$userFolder/error.txt
    fi
fi

END=`date +%s.%N`
runtime=$( echo "$END - $START" | bc -l )
echo $runtime > /$userFolder/executionInfo.txt
