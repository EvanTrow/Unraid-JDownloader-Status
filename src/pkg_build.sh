#!/bin/bash
if [ $# -eq 0 ]; then
    echo "Usage: mkpkg directory_name"
else
    DIR="$(dirname "$(readlink -f ${BASH_SOURCE[0]})")/${@}"
    TMPDIR=/tmp/tmp.$(( $RANDOM * 19318203981230 + 40 ))
    PLUGIN=$(basename ${DIR})
    ARCHIVE="$(dirname $(dirname ${DIR}))/archive"
    DESTDIR="$TMPDIR/usr/local/emhttp/plugins/${PLUGIN}"
    PLG_FILE="$(dirname $(dirname ${DIR}))/${PLUGIN}.plg"
    VERSION=$(date +"%Y.%m.%d")
    ARCH="-x86_64-1"
    PACKAGE="${ARCHIVE}/${PLUGIN}-${VERSION}${ARCH}.txz"


    for x in '' a b c d e d f g h i j k l m n o p q r s t u v w x y z; do
        PKG="${ARCHIVE}/${PLUGIN}-${VERSION}${x}${ARCH}.txz"
        echo "Looking for  ${PKG}"
        if [[ ! -f $PKG ]]; then
          PACKAGE=$PKG
          VERSION="${VERSION}${x}"
          break
        fi
    done

    sed -i -e "s#\(ENTITY\s*version[^\"]*\).*#\1\"${VERSION}\">#" "$PLG_FILE"
    sed -i -e "s#\(ENTITY\s*md5[^\"]*\).*#\1\"${MD5}\">#" "$PLG_FILE"
    sed -i "/##&name/a\###${VERSION}" "$PLG_FILE"

    mkdir -p "${TMPDIR}/"
    cd "$DIR"
    cp --parents -f $(find . -type f ! \( -iname "pkg_build.sh" -o -iname "sftp-config.json" -o -iname ".DS_Store"  \) ) "${TMPDIR}/"
    cd "$TMPDIR/"
    makepkg -l y -c y "${PACKAGE}"
    cd "$ARCHIVE/"
    MD5=$(md5sum $PACKAGE 2>/dev/null|grep -Po '^\S+')
    sed -i -e "s#\(ENTITY\s*md5[^\"]*\).*#\1\"${MD5}\">#" "$PLG_FILE"
    rm -rf "$TMPDIR"
fi
