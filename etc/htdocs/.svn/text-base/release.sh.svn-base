FILESPATH=/home/fligtar/arraise/files

if [ $# -ne 1 ]; then
    echo "Version number required."
    exit
fi
echo "Building arraise $1..."
mkdir ./$1
cd ./$1

echo "Checking out arraise trunk..."
svn co http://arraise.svn.fligtar.com/trunk arraise

echo "Removing tests directory..."
rm -rdf ./arraise/tests

echo "Creating tar.gz..."
tar -pczf arraise-$1.tar.gz arraise

echo "Creating zip..."
zip arraise.zip arraise/*
mv arraise.zip arraise-$1.zip

echo "Copying files to $FILESPATH"
cp arraise-$1.tar.gz $FILESPATH
cp arraise-$1.zip $FILESPATH

echo "Finished."
