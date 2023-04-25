// strange line breaks
function testFunction () {
  const x = document.getElementById('idfromdom')
  // semicolon
  document.getElementById('test').innerHTML = x
}
// two empty lines in a row

// strange line breaks
const testObject = {
  type: 'TestData',
  why: 'for Testing',
  extra: 'Hello'
}

// strange line breaks
const testObject2 = {
  type: 'TestData',
  why: 'for Testing',
  extra: 'Hello'
}

console.log(testObject2)

// missing semicolon
console.log(testObject.type)

// swap comments on off to check if the validator wakes up saying "the testFunction() is never used"
testFunction()

// Bad way to assign a string. Single quatation marks should be used + There is no a new line at the end of file
const badWay = "vaa'ankieli asema"

console.log(badWay)
